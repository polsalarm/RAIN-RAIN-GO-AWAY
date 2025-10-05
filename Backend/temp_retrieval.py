# Import the required Python libraries. They are used to read and plot the data. If any of the following import commands fail, check the local Python environment and install any missing packages.

import json
import numpy as np
from netCDF4 import Dataset
import glob
import os
import datetime

# Read all NetCDF4 files from the data directory
data_directory = 'data'  # Directory containing all .nc4 files
nc4_files = glob.glob(os.path.join(data_directory, '*.nc4'))

if not nc4_files:
    print(f"No .nc4 files found in directory: {data_directory}")
    print("Please create the 'data' directory and put your .nc4 files there.")
    exit()

print(f"Found {len(nc4_files)} .nc4 files in {data_directory} directory:")
for file in sorted(nc4_files):
    print(f"  - {os.path.basename(file)}")

# For now, let's process the first file (you can modify this to process all files later)
data = Dataset(nc4_files[0], mode='r')

# Run the following line below to print MERRA-2 metadata. This line will print attribute and variable information. From the 'variables(dimensions)' list, choose which variable(s) to read in below.
print(data)

# Read in the temperature variable (T) and extract surface level:
lons = data.variables['lon'][:]
lats = data.variables['lat'][:]
# Get temperature data - this is 4D: (time, lev, lat, lon)
# Use masked arrays to handle any fill values or invalid data
T_data = np.ma.filled(data.variables['T'][:,:,:,:], fill_value=np.nan)

# Target coordinates: 14.5995° N, 120.9842° E
target_lat = 14.5995
target_lon = 120.9842

# Find nearest grid points
lat_idx = np.argmin(np.abs(lats - target_lat))
lon_idx = np.argmin(np.abs(lons - target_lon))

# Get actual coordinates of nearest grid point
actual_lat = float(lats[lat_idx])
actual_lon = float(lons[lon_idx])

# Get pressure levels for reference
pressure_levels = data.variables['lev'][:]

# Extract temperature data for all time steps and all pressure levels at this location
T_location = T_data[:, :, lat_idx, lon_idx]  # (time, lev)

# Find the best surface pressure level (highest pressure with valid data)
# Try 1000 hPa first, if it has null values, use 975 hPa
surface_level_idx = 0  # Start with 1000 hPa
if np.all(np.isnan(T_location[:, surface_level_idx])) or np.all(T_location[:, surface_level_idx] == None):
    surface_level_idx = 1  # Use 975 hPa instead

surface_pressure = pressure_levels[surface_level_idx]
surface_temperatures = T_location[:, surface_level_idx]  # Extract only surface level data

# Instead of plotting, write the data and minimal metadata to a JSON file.
out = {}
out['metadata'] = {
	'title': 'MERRA-2 Surface Air Temperature at 14.5995N, 120.9842E',
	'variable': 'T',
	'description': f'Surface temperature at human level ({surface_pressure} hPa pressure level)',
	'target_coordinates': {'lat': target_lat, 'lon': target_lon},
	'actual_coordinates': {'lat': actual_lat, 'lon': actual_lon},
	'data_source': os.path.basename(nc4_files[0]),
	'total_files_available': len(nc4_files),
	'data_directory': data_directory
}

# try to capture units if present
t_var = data.variables.get('T')
if t_var is not None:
	units = getattr(t_var, 'units', None)
	if units is not None:
		out['metadata']['units'] = units

# Get time information with proper error handling
time_var = data.variables['time']
try:
    # Use masked arrays to handle invalid values properly
    times = np.ma.filled(time_var[:], fill_value=np.nan)
    # Filter out any NaN or invalid values
    times = times[np.isfinite(times)]
except Exception as e:
    print(f"Warning: Issue reading time data: {e}")
    # Fallback: create a simple time index
    times = np.arange(len(time_var))
    
time_units = getattr(time_var, 'units', 'unknown')

# Convert data to JSON-serializable format with NaN handling
out['pressure_level'] = float(surface_pressure)  # Single pressure level instead of array
out['times'] = np.asarray(times).tolist()
out['time_units'] = time_units

# Handle potential NaN values in surface temperature data
surface_temps_clean = np.where(np.isfinite(surface_temperatures), surface_temperatures, None)
out['surface_temperature_data'] = np.asarray(surface_temps_clean).tolist()  # [time] - single value per time

# Add Celsius conversion for convenience
celsius_temps = []
for temp_k in surface_temps_clean:
    if temp_k is not None and np.isfinite(temp_k):
        celsius_temps.append(round(temp_k - 273.15, 2))  # Convert K to C and round to 2 decimals
    else:
        celsius_temps.append(None)
out['temperature_celsius'] = celsius_temps

# Add human-readable time stamps
readable_times = []
import datetime
base_time = datetime.datetime(2015, 1, 2, 0, 0, 0)  # 2015-01-02 00:00:00
for time_minutes in times:
    time_obj = base_time + datetime.timedelta(minutes=int(time_minutes))
    readable_times.append(time_obj.strftime('%Y-%m-%d %H:%M:%S UTC'))
out['time_readable'] = readable_times

with open('MERRA2_t2m.json', 'w', encoding='utf-8') as f:
	json.dump(out, f, indent=2)

print("Wrote MERRA2_t2m.json with keys:", list(out.keys()))