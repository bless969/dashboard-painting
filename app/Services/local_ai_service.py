"""
Local AI Detection Service for Paint Line Object Detection (YOLO / Ultralytics)
Supports both standalone CLI invocation and persistent background HTTP daemon (port 5001).
100% Offline, no internet required.
"""
import sys
import os
import json
import base64
import time
import argparse
from http.server import HTTPServer, BaseHTTPRequestHandler
import numpy as np
import cv2

# Lazy model cache for daemon mode
LOADED_MODELS = {}

def get_model(model_path):
    if not os.path.exists(model_path):
        raise FileNotFoundError(f"Model file not found: {model_path}")
    if model_path not in LOADED_MODELS:
        from ultralytics import YOLO
        # Disable verbose logging and online checks
        os.environ['YOLO_VERBOSE'] = 'False'
        LOADED_MODELS[model_path] = YOLO(model_path)
    return LOADED_MODELS[model_path]

def decode_image_base64(b64_str):
    if ',' in b64_str:
        b64_str = b64_str.split(',', 1)[1]
    img_bytes = base64.b64decode(b64_str)
    nparr = np.frombuffer(img_bytes, np.uint8)
    img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
    return img

def run_inference(model_path, img, conf_threshold=0.35):
    t_start = time.perf_counter()
    model = get_model(model_path)
    
    # Run prediction
    results = model(img, conf=conf_threshold, verbose=False)[0]
    t_end = time.perf_counter()
    latency_ms = round((t_end - t_start) * 1000, 1)

    predictions = []
    part_count = 0
    hanger_count = 0

    if results.boxes is not None and len(results.boxes) > 0:
        for box in results.boxes:
            xywh = box.xywh[0].tolist()  # [center_x, center_y, width, height]
            conf = float(box.conf[0])
            cls_id = int(box.cls[0])
            cls_name = model.names.get(cls_id, f"class_{cls_id}")

            cls_lower = cls_name.lower()
            is_hanger = any(k in cls_lower for k in ['hanger', 'gantungan', 'kosong', 'empty'])
            if is_hanger:
                hanger_count += 1
            else:
                part_count += 1

            predictions.append({
                "x": round(xywh[0], 1),
                "y": round(xywh[1], 1),
                "width": round(xywh[2], 1),
                "height": round(xywh[3], 1),
                "confidence": round(conf, 3),
                "class": cls_name,
                "class_id": cls_id,
                "is_hanger": is_hanger
            })

    return {
        "status": "ok",
        "model_file": os.path.basename(model_path),
        "latency_ms": latency_ms,
        "part_count": part_count,
        "hanger_count": hanger_count,
        "total_detections": len(predictions),
        "predictions": predictions
    }

class AIDetectionHandler(BaseHTTPRequestHandler):
    def _set_headers(self, status=200):
        self.send_response(status)
        self.send_header('Content-Type', 'application/json')
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', '*')
        self.end_headers()

    def do_OPTIONS(self):
        self._set_headers(204)

    def do_GET(self):
        if self.path == '/status' or self.path == '/':
            self._set_headers(200)
            self.wfile.write(json.dumps({
                "status": "ok",
                "service": "Offline YOLO AI Service",
                "loaded_models": list(LOADED_MODELS.keys())
            }).encode('utf-8'))
        else:
            self._set_headers(404)
            self.wfile.write(json.dumps({"status": "error", "message": "Not found"}).encode('utf-8'))

    def do_POST(self):
        content_length = int(self.headers.get('Content-Length', 0))
        post_data = self.rfile.read(content_length)
        
        try:
            payload = json.loads(post_data.decode('utf-8'))
            model_path = payload.get('model_path', '')
            conf = float(payload.get('conf', 0.35))
            
            # Default model path if relative
            if not os.path.isabs(model_path):
                base_dir = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
                model_path = os.path.join(base_dir, 'storage', 'app', 'models', model_path)
            
            # Read image from base64 or file
            if 'image_base64' in payload:
                img = decode_image_base64(payload['image_base64'])
            elif 'image_path' in payload and os.path.exists(payload['image_path']):
                img = cv2.imread(payload['image_path'])
            else:
                self._set_headers(400)
                self.wfile.write(json.dumps({"status": "error", "message": "Missing image_base64 or valid image_path"}).encode('utf-8'))
                return

            if img is None:
                self._set_headers(400)
                self.wfile.write(json.dumps({"status": "error", "message": "Failed to decode image"}).encode('utf-8'))
                return

            result = run_inference(model_path, img, conf)
            self._set_headers(200)
            self.wfile.write(json.dumps(result).encode('utf-8'))

        except Exception as e:
            self._set_headers(500)
            self.wfile.write(json.dumps({"status": "error", "message": str(e)}).encode('utf-8'))

    def log_message(self, format, *args):
        # Suppress standard logging to keep console clean
        pass

def main():
    parser = argparse.ArgumentParser(description="Offline YOLO AI Detection Service")
    parser.add_argument('--serve', action='store_true', help="Run as background HTTP daemon")
    parser.add_argument('--port', type=int, default=5001, help="Port for HTTP daemon")
    parser.add_argument('--model', type=str, default="", help="Path to .pt or .onnx model")
    parser.add_argument('--image-file', type=str, default="", help="Path to input image file")
    parser.add_argument('--image-base64', type=str, default="", help="Base64 encoded input image")
    parser.add_argument('--conf', type=float, default=0.35, help="Confidence threshold")

    args = parser.parse_args()

    if args.serve:
        server_address = ('127.0.0.1', args.port)
        httpd = HTTPServer(server_address, AIDetectionHandler)
        print(f"Offline YOLO AI Server listening on http://127.0.0.1:{args.port}")
        # Warmup model if provided
        if args.model and os.path.exists(args.model):
            print(f"Pre-loading model: {args.model}")
            get_model(args.model)
        httpd.serve_forever()
    else:
        # CLI direct mode
        if not args.model:
            print(json.dumps({"status": "error", "message": "Model path required (--model)"}))
            sys.exit(1)

        img = None
        if args.image_file and os.path.exists(args.image_file):
            img = cv2.imread(args.image_file)
        elif args.image_base64:
            img = decode_image_base64(args.image_base64)
        else:
            # Try reading from stdin
            stdin_data = sys.stdin.read().strip()
            if stdin_data:
                try:
                    payload = json.loads(stdin_data)
                    if 'image_base64' in payload:
                        img = decode_image_base64(payload['image_base64'])
                    if not args.model and 'model_path' in payload:
                        args.model = payload['model_path']
                except:
                    img = decode_image_base64(stdin_data)

        if img is None:
            print(json.dumps({"status": "error", "message": "No valid input image provided"}))
            sys.exit(1)

        result = run_inference(args.model, img, args.conf)
        print(json.dumps(result))

if __name__ == '__main__':
    main()
