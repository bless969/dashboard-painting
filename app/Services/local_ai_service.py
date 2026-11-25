"""
Local AI Detection Service with Ultralytics YOLO & Supervision (ByteTrack)
Features:
- Ultralytics YOLO (v8, v11, ONNX)
- Native ByteTrack tracking via Ultralytics & Supervision (assigns consistent tracker_id)
- FastAPI + Uvicorn daemon on port 5001
- CLI fallback mode for direct PHP shell execution
- 100% Offline, no external cloud / Roboflow dependency required
"""
import sys
import os
import warnings
warnings.filterwarnings('ignore')
import json
import base64
import time
import argparse
import numpy as np
import cv2
import supervision as sv

# Lazy model cache for daemon mode
LOADED_MODELS = {}

def get_model(model_path):
    if not os.path.exists(model_path):
        raise FileNotFoundError(f"File model tidak ditemukan: {model_path}")
    if model_path not in LOADED_MODELS:
        from ultralytics import YOLO
        os.environ['YOLO_VERBOSE'] = 'False'
        try:
            LOADED_MODELS[model_path] = YOLO(model_path)
        except TypeError as te:
            if "references types outside the supported Ultralytics checkpoint format" in str(te):
                raise ValueError(
                    f"Format weights model '{os.path.basename(model_path)}' tidak kompatibel dengan Ultralytics YOLO lokal (terdeteksi format Roboflow RT-DETR). Silakan ekspor/unduh dari Roboflow dalam format 'YOLOv8 PyTorch' atau 'YOLOv11 PyTorch' (.pt) atau ONNX."
                )
            raise
        except Exception as e:
            raise RuntimeError(f"Gagal memuat model '{os.path.basename(model_path)}': {str(e)}")
    return LOADED_MODELS[model_path]


def decode_image_base64(b64_str):
    if ',' in b64_str:
        b64_str = b64_str.split(',', 1)[1]
    img_bytes = base64.b64decode(b64_str)
    nparr = np.frombuffer(img_bytes, np.uint8)
    img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
    return img


def run_inference(model_path, img, conf_threshold=0.35, use_tracking=True):
    t_start = time.perf_counter()
    model = get_model(model_path)

    # Jalankan prediksi dengan tracking ByteTrack (anti hitung ganda)
    try:
        if use_tracking:
            results = model.track(img, persist=True, tracker="bytetrack.yaml", conf=conf_threshold, verbose=False)[0]
        else:
            results = model(img, conf=conf_threshold, verbose=False)[0]
    except Exception as ePredict:
        # Fallback ke inference standar jika tracker ada issue
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

            # Tracker ID dari ByteTrack
            tracker_id = None
            if box.id is not None:
                try:
                    tracker_id = int(box.id[0])
                except:
                    pass

            cls_lower = cls_name.lower()
            is_hanger = any(k in cls_lower for k in ['hanger', 'gantungan', 'kosong', 'empty'])
            if is_hanger:
                hanger_count += 1
            else:
                part_count += 1

            pred_item = {
                "x": round(xywh[0], 1),
                "y": round(xywh[1], 1),
                "width": round(xywh[2], 1),
                "height": round(xywh[3], 1),
                "confidence": round(conf, 3),
                "class": cls_name,
                "class_id": cls_id,
                "is_hanger": is_hanger
            }
            if tracker_id is not None:
                pred_item["tracker_id"] = tracker_id

            predictions.append(pred_item)

    return {
        "status": "ok",
        "model_file": os.path.basename(model_path),
        "latency_ms": latency_ms,
        "part_count": part_count,
        "hanger_count": hanger_count,
        "total_detections": len(predictions),
        "tracking_enabled": use_tracking,
        "supervision_version": getattr(sv, '__version__', '0.32.0'),
        "predictions": predictions
    }


def create_fastapi_app():
    from fastapi import FastAPI, Request
    from fastapi.responses import JSONResponse
    from fastapi.middleware.cors import CORSMiddleware

    app = FastAPI(title="Offline YOLO & Supervision AI Service", version="2.0")

    app.add_middleware(
        CORSMiddleware,
        allow_origins=["*"],
        allow_credentials=True,
        allow_methods=["*"],
        allow_headers=["*"],
    )

    @app.get("/")
    @app.get("/status")
    def status():
        return {
            "status": "ok",
            "service": "Offline YOLO + Supervision (ByteTrack) AI Service",
            "supervision_version": getattr(sv, '__version__', 'N/A'),
            "loaded_models": list(LOADED_MODELS.keys())
        }

    @app.post("/reset-tracker")
    def reset_tracker():
        # Reset internal tracker state di semua loaded models
        for m in LOADED_MODELS.values():
            if hasattr(m, 'predictor') and m.predictor and hasattr(m.predictor, 'trackers'):
                m.predictor.trackers = []
        return {"status": "ok", "message": "ByteTrack tracker IDs berhasil di-reset"}

    @app.post("/")
    @app.post("/detect")
    async def detect(request: Request):
        try:
            payload = await request.json()
            model_path = payload.get('model_path', '')
            conf = float(payload.get('conf', 0.35))
            use_tracking = bool(payload.get('use_tracker', True))

            if not os.path.isabs(model_path):
                base_dir = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
                if os.path.exists(model_path):
                    model_path = os.path.abspath(model_path)
                elif os.path.exists(os.path.join(base_dir, model_path)):
                    model_path = os.path.join(base_dir, model_path)
                else:
                    model_path = os.path.join(base_dir, 'storage', 'app', 'models', os.path.basename(model_path))

            if 'image_base64' in payload and payload['image_base64']:
                img = decode_image_base64(payload['image_base64'])
            elif 'image_path' in payload and os.path.exists(payload['image_path']):
                img = cv2.imread(payload['image_path'])
            else:
                return JSONResponse(status_code=400, content={"status": "error", "message": "Gambar tidak ditemukan (image_base64 kosong)"})

            if img is None:
                return JSONResponse(status_code=400, content={"status": "error", "message": "Gagal mendecode gambar base64"})

            result = run_inference(model_path, img, conf, use_tracking)
            return result

        except Exception as e:
            return JSONResponse(status_code=500, content={"status": "error", "message": str(e)})

    return app


def main():
    parser = argparse.ArgumentParser(description="Offline YOLO & Supervision AI Service")
    parser.add_argument('--serve', action='store_true', help="Run as FastAPI background server on port 5001")
    parser.add_argument('--port', type=int, default=5001, help="Port for server")
    parser.add_argument('--model', type=str, default="", help="Path to .pt or .onnx model")
    parser.add_argument('--image-file', type=str, default="", help="Path to input image file")
    parser.add_argument('--image-base64', type=str, default="", help="Base64 encoded input image")
    parser.add_argument('--conf', type=float, default=0.35, help="Confidence threshold")

    args = parser.parse_args()

    if args.serve:
        import uvicorn
        if args.model and os.path.exists(args.model):
            try:
                print(f"Pre-warming model: {args.model}")
                get_model(args.model)
            except Exception as eWarm:
                print(f"Warmup warning: {eWarm}")

        app = create_fastapi_app()
        print(f"Starting Offline YOLO + Supervision Server on http://127.0.0.1:{args.port}...")
        uvicorn.run(app, host="127.0.0.1", port=args.port, log_level="warning")
    else:
        # CLI direct mode
        if not args.model:
            print(json.dumps({"status": "error", "message": "Parameter --model wajib diisi"}))
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
            print(json.dumps({"status": "error", "message": "Gambar tidak valid"}))
            sys.exit(1)

        try:
            result = run_inference(args.model, img, args.conf)
            print(json.dumps(result))
        except Exception as e:
            print(json.dumps({"status": "error", "message": str(e)}))


if __name__ == '__main__':
    main()
