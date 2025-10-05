import json
import os
from flask import Flask, jsonify, send_file, request, abort
from flask_cors import CORS

# Path to the JSON file produced by trylang.py
DATA_PATH = os.path.join(os.path.dirname(__file__), 'MERRA2_t2m.json')


def load_data():
    if not os.path.exists(DATA_PATH):
        return None
    with open(DATA_PATH, 'r', encoding='utf-8') as fh:
        return json.load(fh)


app = Flask(__name__)
CORS(app)

data = load_data()
if data is None:
    print(f"Warning: data file not found at {DATA_PATH}")


@app.route('/')
def index():
    return jsonify({
        'message': 'MERRA2 backend',
        'endpoints': ['/data', '/metadata', '/t2m', '/download']
    })


@app.route('/data')
def get_data():
    if data is None:
        abort(404, 'data file not found')
    return jsonify(data)


@app.route('/metadata')
def get_metadata():
    if data is None:
        abort(404, 'data file not found')
    return jsonify(data.get('metadata', {}))


@app.route('/t2m')
def get_t2m():
    if data is None:
        abort(404, 'data file not found')

    # optional downsample query parameter: ?downsample=2
    ds_param = request.args.get('downsample', default='1')
    try:
        ds = int(ds_param)
        if ds < 1:
            ds = 1
    except Exception:
        ds = 1

    lons = data.get('lons', [])
    lats = data.get('lats', [])
    T2M = data.get('T2M', [])

    if ds == 1:
        return jsonify({'lons': lons, 'lats': lats, 'T2M': T2M})

    # Downsample by simple slicing. Assumes T2M is a list of rows (lat-major).
    lons_ds = lons[::ds]
    lats_ds = lats[::ds]
    try:
        T2M_ds = [row[::ds] for row in T2M[::ds]]
    except Exception:
        # In case of unexpected shape, fall back to original
        T2M_ds = T2M

    return jsonify({'lons': lons_ds, 'lats': lats_ds, 'T2M': T2M_ds, 'downsample': ds})


@app.route('/download')
def download():
    if not os.path.exists(DATA_PATH):
        abort(404, 'data file not found')
    return send_file(DATA_PATH, as_attachment=True)


if __name__ == '__main__':
    # Default dev server. Use a proper WSGI server in production.
    app.run(host='0.0.0.0', port=5000, debug=True)
