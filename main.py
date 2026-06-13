import os
import shutil
import tempfile
import json
from flask import Flask, request, jsonify
from train_models import train_and_optimize

app = Flask(__name__)

@app.route('/health', methods=['GET'])
def health():
    return jsonify({"status": "healthy"}), 200

@app.route('/train', methods=['POST'])
def train():
    if 'file' not in request.files:
        return jsonify({"success": False, "error": "No file part in the request"}), 400
        
    file = request.files['file']
    if file.filename == '':
        return jsonify({"success": False, "error": "No selected file"}), 400
        
    original_filename = request.form.get('original_filename', file.filename)
    
    # Create a temporary directory for execution
    temp_dir = tempfile.mkdtemp()
    try:
        temp_excel_path = os.path.join(temp_dir, 'uploaded.xlsx')
        file.save(temp_excel_path)
        
        # Run training using exact logic from train_models.py
        success = train_and_optimize(temp_excel_path, temp_dir, original_filename)
        
        if not success:
            return jsonify({"success": False, "error": "Model training pipeline failed. Please check Excel format."}), 500
            
        # Read the generated output files into strings
        outputs = {}
        files_to_read = {
            'hasil_forecast': 'hasil_forecast.csv',
            'hasil_akhir': 'hasil_akhir.csv',
            'model_evaluation': 'model_evaluation.csv',
            'data_raw': 'data_raw.csv'
        }
        
        for key, filename in files_to_read.items():
            filepath = os.path.join(temp_dir, filename)
            if os.path.exists(filepath):
                with open(filepath, 'r', encoding='utf-8') as f:
                    outputs[key] = f.read()
            else:
                outputs[key] = ""
                
        # Read dataset_info.json
        info_path = os.path.join(temp_dir, 'dataset_info.json')
        if os.path.exists(info_path):
            with open(info_path, 'r', encoding='utf-8') as f:
                outputs['dataset_info'] = json.load(f)
        else:
            outputs['dataset_info'] = {}
            
        return jsonify({
            "success": True,
            "outputs": outputs
        }), 200
        
    except Exception as e:
        return jsonify({"success": False, "error": str(e)}), 500
        
    finally:
        # Clean up temporary execution directory
        shutil.rmtree(temp_dir, ignore_errors=True)

if __name__ == '__main__':
    port = int(os.environ.get('PORT', 5000))
    app.run(host='0.0.0.0', port=port)
