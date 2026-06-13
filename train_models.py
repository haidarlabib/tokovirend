import pandas as pd
import numpy as np
import os
import sys
import statsmodels.api as sm
import json
from datetime import datetime, timedelta

def train_and_optimize(excel_path, output_dir, original_filename=None):
    print(f"Processing Excel file: {excel_path}")
    if not os.path.exists(excel_path):
        print(f"Error: Excel file does not exist at {excel_path}")
        return False
        
    try:
        # Read the Excel file
        df = pd.read_excel(excel_path)
        
        # Standardize column names
        df.columns = [c.strip().lower() for c in df.columns]
        expected_cols = ['tanggal', 'produk', 'qty', 'total_harga']
        for col in expected_cols:
            if col not in df.columns:
                print(f"Error: Missing column '{col}' in uploaded Excel file.")
                return False
                
        # Clean data
        df['tanggal'] = pd.to_datetime(df['tanggal'])
        df['qty'] = pd.to_numeric(df['qty'], errors='coerce').fillna(0).astype(int)
        df['total_harga'] = pd.to_numeric(df['total_harga'], errors='coerce').fillna(0).astype(int)
        
        # Calculate daily sales by date and product
        df_daily = df.groupby(['tanggal', 'produk']).agg({'qty': 'sum', 'total_harga': 'sum'}).reset_index()
        
        # Calculate price (handle zero division)
        df_daily['harga'] = np.where(df_daily['qty'] > 0, df_daily['total_harga'] / df_daily['qty'], 0.0)
        
        # Filter for active modeling
        products = df_daily['produk'].unique()
        print(f"Unique products found: {products}")
        
        elasticity_results = {}
        forecast_daily_list = []
        model_eval_rows = []
        
        # Process each product
        for p in products:
            p_df = df_daily[df_daily['produk'] == p].sort_values('tanggal').copy()
            if len(p_df) < 5:
                print(f"Skipping product {p} due to insufficient data rows.")
                continue
                
            # --- 1. Elasticity (Log-Log Regression) ---
            # ln_Q = E * ln_P + alpha
            el_df = p_df[(p_df['qty'] > 0) & (p_df['harga'] > 0)].copy()
            el_df['ln_P'] = np.log(el_df['harga'])
            el_df['ln_Q'] = np.log(el_df['qty'])
            
            if len(el_df) > 1:
                # OLS via statsmodels
                X_el = sm.add_constant(el_df['ln_P'])
                y_el = el_df['ln_Q']
                model_el = sm.OLS(y_el, X_el).fit()
                
                elasticity = model_el.params['ln_P'] if 'ln_P' in model_el.params else 0.0
                el_r2 = model_el.rsquared
                el_adj_r2 = model_el.rsquared_adj
                el_f_stat = model_el.fvalue
                el_f_pval = model_el.f_pvalue
                # Status model: Layak if F-stat p-value < 0.05
                el_status = "Layak" if el_f_pval < 0.05 else "Tidak Layak"
            else:
                elasticity = 0.0
                el_r2, el_adj_r2, el_f_stat, el_f_pval = 0.0, 0.0, 0.0, 1.0
                el_status = "Tidak Layak"
            
            # Kategori
            abs_E = abs(elasticity)
            kategori = "Elastis" if abs_E > 1 else "Inelastis"
            
            # --- 2. Forecasting (Semi-Log Regression) ---
            # ln_Q = slope * t + intercept
            fc_df = p_df[p_df['qty'] > 0].copy()
            fc_df['t'] = range(1, len(fc_df) + 1)
            fc_df['ln_Q'] = np.log(fc_df['qty'])
            
            if len(fc_df) > 1:
                X_fc = sm.add_constant(fc_df['t'])
                y_fc = fc_df['ln_Q']
                model_fc = sm.OLS(y_fc, X_fc).fit()
                
                fc_slope = model_fc.params['t'] if 't' in model_fc.params else 0.0
                fc_intercept = model_fc.params['const'] if 'const' in model_fc.params else 0.0
                
                # Evaluation metrics on original scale (Q vs Q_fit)
                fc_df['qty_fit'] = np.exp(model_fc.fittedvalues)
                actual_q = fc_df['qty'].values
                fitted_q = fc_df['qty_fit'].values
                
                fc_mae = float(np.mean(np.abs(actual_q - fitted_q)))
                fc_rmse = float(np.sqrt(np.mean((actual_q - fitted_q) ** 2)))
                
                non_zero = actual_q > 0
                fc_mape = float(np.mean(np.abs(actual_q[non_zero] - fitted_q[non_zero]) / actual_q[non_zero]) * 100) if np.any(non_zero) else 0.0
                
                ss_res = np.sum((actual_q - fitted_q) ** 2)
                ss_tot = np.sum((actual_q - np.mean(actual_q)) ** 2)
                fc_r2 = float(1.0 - (ss_res / ss_tot) if ss_tot > 0 else 0.0)
                
                # Kategori MAPE
                if fc_mape < 10.0:
                    fc_kategori = "Sangat Baik"
                elif fc_mape <= 20.0:
                    fc_kategori = "Baik"
                elif fc_mape <= 50.0:
                    fc_kategori = "Cukup"
                else:
                    fc_kategori = "Kurang Baik"
            else:
                fc_slope, fc_intercept = 0.0, 0.0
                fc_mae, fc_rmse, fc_mape, fc_r2 = 0.0, 0.0, 0.0, 0.0
                fc_kategori = "Kurang Baik"
                
            # Get last time index and date
            last_t = len(p_df)
            last_date = p_df['tanggal'].max()
            
            # Get P_awal (median price of recent historical data or overall)
            p_awal = p_df[p_df['harga'] > 0]['harga'].median()
            if np.isnan(p_awal) or p_awal <= 0:
                p_awal = 1000.0 # default fallback
                
            # Round current price to nearest 500 or standard format
            p_awal = int(round(p_awal))
            
            # Generate 90 days daily forecast
            daily_forecasts = []
            for day in range(1, 91):
                t_future = last_t + day
                date_future = last_date + pd.Timedelta(days=day)
                qty_future = np.exp(fc_intercept + fc_slope * t_future)
                daily_forecasts.append({
                    'produk': p,
                    'tanggal': date_future.strftime('%Y-%m-%d'),
                    'qty_forecast': qty_future
                })
                
            forecast_daily_list.extend(daily_forecasts)
            
            # Sum forecast for 90 days
            qty_forecast_90 = sum([f['qty_forecast'] for f in daily_forecasts])
            
            # --- 3. Optimization ---
            # Search from -20% to +50% of current price with 1% steps
            best_price = p_awal
            best_revenue = 0.0
            best_qty = 0.0
            
            for pct in range(-20, 51):
                p_test = p_awal * (1 + pct / 100.0)
                qty_test = qty_forecast_90 * ((p_test / p_awal) ** elasticity)
                rev_test = p_test * qty_test
                if rev_test > best_revenue:
                    best_revenue = rev_test
                    best_price = p_test
                    best_qty = qty_test
                    
            elasticity_results[p] = {
                'elastisitas': elasticity,
                'kategori': kategori,
                'qty_forecast': int(round(qty_forecast_90)),
                'harga_saat_ini': int(round(p_awal)),
                'harga_optimal': int(round(best_price)),
                'qty_optimal': int(round(best_qty)),
                'revenue_maksimum': int(round(best_revenue))
            }
            
            # Append model evaluation info
            model_eval_rows.append({
                'produk': p,
                'r2_elastisitas': float(el_r2),
                'adj_r2_elastisitas': float(el_adj_r2),
                'f_stat_elastisitas': float(el_f_stat),
                'p_value_elastisitas': float(el_f_pval),
                'status_model_elastisitas': el_status,
                'r2_forecast': float(fc_r2),
                'mae_forecast': float(fc_mae),
                'rmse_forecast': float(fc_rmse),
                'mape_forecast': float(fc_mape),
                'kategori_forecast': fc_kategori
            })
            
        # Write to hasil_forecast.csv
        df_forecast_out = pd.DataFrame(forecast_daily_list)
        forecast_path = os.path.join(output_dir, 'hasil_forecast.csv')
        df_forecast_out.to_csv(forecast_path, index=False)
        print(f"Saved daily forecast to {forecast_path}")
        
        # Write to hasil_akhir.csv
        final_rows = []
        for p, res in elasticity_results.items():
            final_rows.append({
                'produk': p,
                'elastisitas': round(res['elastisitas'], 4),
                'kategori': res['kategori'],
                'qty_forecast': res['qty_forecast'],
                'harga_saat_ini': res['harga_saat_ini'],
                'harga_optimal': res['harga_optimal'],
                'qty_optimal': res['qty_optimal'],
                'revenue_maksimum': res['revenue_maksimum']
            })
            
        df_final = pd.DataFrame(final_rows)
        final_path = os.path.join(output_dir, 'hasil_akhir.csv')
        df_final.to_csv(final_path, index=False)
        print(f"Saved active summary to {final_path}")
        
        # Write to hasil_optimasi.xlsx (for excel compatibility)
        optim_excel_path = os.path.join(output_dir, 'hasil_optimasi.xlsx')
        df_optim_excel = df_final[['produk', 'harga_optimal', 'qty_optimal', 'revenue_maksimum']]
        df_optim_excel.to_excel(optim_excel_path, index=False)
        print(f"Saved excel optimization to {optim_excel_path}")
        
        # Write to model_evaluation.csv
        df_eval = pd.DataFrame(model_eval_rows)
        eval_path = os.path.join(output_dir, 'model_evaluation.csv')
        df_eval.to_csv(eval_path, index=False)
        print(f"Saved model evaluation to {eval_path}")
        
        # Write to data_raw.csv (standardized raw transactional data)
        df['harga_per_unit'] = np.where(df['qty'] > 0, df['total_harga'] / df['qty'], 0.0)
        df_raw = df[['tanggal', 'produk', 'qty', 'total_harga', 'harga_per_unit']].copy()
        df_raw['tanggal'] = df_raw['tanggal'].dt.strftime('%Y-%m-%d')
        raw_csv_path = os.path.join(output_dir, 'data_raw.csv')
        df_raw.to_csv(raw_csv_path, index=False)
        print(f"Saved raw data to {raw_csv_path}")
        
        # Helper for Indonesian dates
        def format_date_indo(dt):
            months = {
                1: 'Jan', 2: 'Feb', 3: 'Mar', 4: 'Apr', 5: 'Mei', 6: 'Jun',
                7: 'Jul', 8: 'Agu', 9: 'Sep', 10: 'Okt', 11: 'Nov', 12: 'Des'
            }
            return f"{dt.day:02d} {months[dt.month]} {dt.year}"
            
        # Use UTC+7 to force Jakarta timezone (WIB) on Railway hosting
        now = datetime.utcnow() + timedelta(hours=7)
        upload_time_str = f"{now.day:02d} {format_date_indo(now).split()[1]} {now.year} | {now.strftime('%H:%M')} WIB"

        # Write to dataset_info.json
        info_path = os.path.join(output_dir, 'dataset_info.json')
        info_data = {
            'filename': original_filename if original_filename else os.path.basename(excel_path),
            'upload_time': upload_time_str,
            'total_records': int(len(df)),
            'total_columns': int(len(df.columns)),
            'date_range_start': format_date_indo(df['tanggal'].min()),
            'date_range_end': format_date_indo(df['tanggal'].max()),
            'products': [str(p) for p in products]
        }
        with open(info_path, 'w') as f:
            json.dump(info_data, f, indent=4)
        print(f"Saved dataset info to {info_path}")
        
        return True
        
    except Exception as e:
        print(f"Error training models: {e}")
        import traceback
        traceback.print_exc()
        return False

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Usage: python train_models.py <excel_path> <output_dir> [original_filename]")
        sys.exit(1)
    excel_path = sys.argv[1]
    output_dir = sys.argv[2]
    original_filename = sys.argv[3] if len(sys.argv) > 3 else None
    success = train_and_optimize(excel_path, output_dir, original_filename)
    sys.exit(0 if success else 1)
