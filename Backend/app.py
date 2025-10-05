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
        'endpoints': ['/data', '/metadata', '/temperature', '/t2m', '/download', '/health']
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


@app.route('/temperature')
def get_temperature():
    """Get temperature data in the new format"""
    if data is None:
        abort(404, 'data file not found')
    
    # Extract temperature data from the new structure
    temperature_data = {
        'times': data.get('times', []),
        'time_units': data.get('time_units', 'unknown'),
        'time_readable': data.get('time_readable', []),
        'surface_temperature_kelvin': data.get('surface_temperature_data', []),
        'temperature_celsius': data.get('temperature_celsius', []),
        'pressure_level': data.get('pressure_level', None),
        'coordinates': {
            'target': data.get('metadata', {}).get('target_coordinates', {}),
            'actual': data.get('metadata', {}).get('actual_coordinates', {})
        }
    }
    
    # Optional filtering by date range
    start_date = request.args.get('start_date')
    end_date = request.args.get('end_date')
    
    if start_date or end_date:
        try:
            # Filter data by date if parameters provided
            # This would require parsing the time_readable array
            pass  # Implement date filtering if needed
        except Exception as e:
            # If filtering fails, return all data
            pass
    
    return jsonify(temperature_data)


@app.route('/t2m')
def get_t2m():
    """Legacy endpoint for backwards compatibility"""
    if data is None:
        abort(404, 'data file not found')
    
    # Convert new format to old T2M-like format for compatibility
    times = data.get('times', [])
    temps_celsius = data.get('temperature_celsius', [])
    
    # Create a simplified format that mimics the old T2M structure
    legacy_data = {
        'times': times,
        'T2M': temps_celsius,  # Use Celsius temperatures as T2M
        'coordinates': data.get('metadata', {}).get('actual_coordinates', {}),
        'note': 'Legacy format - use /temperature endpoint for full data'
    }
    
    return jsonify(legacy_data)


@app.route('/download')
def download():
    if not os.path.exists(DATA_PATH):
        abort(404, 'data file not found')
    return send_file(DATA_PATH, as_attachment=True)


@app.route('/current')
def get_current():
    """Get the most recent temperature reading"""
    if data is None:
        abort(404, 'data file not found')
    
    temps_celsius = data.get('temperature_celsius', [])
    times_readable = data.get('time_readable', [])
    
    if not temps_celsius or not times_readable:
        abort(404, 'no temperature data available')
    
    # Find the most recent non-null temperature
    for i in range(len(temps_celsius) - 1, -1, -1):
        if temps_celsius[i] is not None:
            return jsonify({
                'current_temperature_celsius': temps_celsius[i],
                'time': times_readable[i] if i < len(times_readable) else 'unknown',
                'coordinates': data.get('metadata', {}).get('actual_coordinates', {}),
                'location': 'Manila Area (14.60°N, 120.98°E)'
            })
    
    abort(404, 'no valid temperature data found')


@app.route('/health')
def health_check():
    """Health check endpoint for VS Code port forwarding verification"""
    import datetime
    
    # Check data availability and structure
    data_info = {}
    if data is not None:
        data_info = {
            'has_temperature_data': 'temperature_celsius' in data,
            'has_metadata': 'metadata' in data,
            'data_points': len(data.get('temperature_celsius', [])),
            'time_range': {
                'start': data.get('time_readable', [None])[0] if data.get('time_readable') else None,
                'end': data.get('time_readable', [None])[-1] if data.get('time_readable') else None
            }
        }
    
    return jsonify({
        'status': 'healthy',
        'service': 'RAIN-GO-AWAY Backend',
        'port_forwarding': 'VS Code compatible',
        'data_available': data is not None,
        'data_info': data_info,
        'timestamp': datetime.datetime.now().isoformat(),
        'endpoints': [
            'GET / - API info',
            'GET /health - Health check', 
            'GET /data - Full dataset',
            'GET /metadata - Dataset metadata',
            'GET /temperature - New temperature endpoint',
            'GET /t2m - Legacy temperature endpoint',
            'GET /current - Latest temperature reading',
            'GET /download - Download JSON file'
        ]
    })


if __name__ == '__main__':
    # Configuration for VS Code port forwarding
    # The host='0.0.0.0' allows VS Code to properly forward the port
    # Port 5000 will be automatically detected by VS Code for forwarding
    import sys
    
    port = int(os.environ.get('PORT', 5000))
    host = '0.0.0.0'  # Essential for VS Code port forwarding
    
    print(f"🌧️  RAIN-GO-AWAY Backend starting...")
    print(f"🚀 Server running on {host}:{port}")
    print(f"📡 VS Code will auto-detect this port for forwarding")
    print(f"🔗 Access via: http://localhost:{port}")
    print(f"📊 Available endpoints:")
    print(f"   - GET / (API info)")
    print(f"   - GET /health (health check)")
    print(f"   - GET /data (full dataset)")
    print(f"   - GET /metadata (dataset info)")
    print(f"   - GET /temperature (new temperature data)")
    print(f"   - GET /current (latest temperature)")
    print(f"   - GET /t2m (legacy temperature data)")
    print(f"   - GET /download (download JSON file)")
    
    try:
        app.run(host=host, port=port, debug=True, threaded=True)
    except KeyboardInterrupt:
        print(f"\n🛑 Server stopped by user")
        sys.exit(0)
    except Exception as e:
        print(f"❌ Error starting server: {e}")
        sys.exit(1)
