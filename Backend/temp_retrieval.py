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

# Target coordinates: 14.5995° N, 120.9842° E
target_lat = 14.5995
target_lon = 120.9842

# Initialize combined output structure
out = {}
out['metadata'] = {
	'title': 'MERRA-2 Surface Air Temperature at 14.5995N, 120.9842E',
	'variable': 'T',
	'target_coordinates': {'lat': target_lat, 'lon': target_lon},
	'total_files_processed': len(nc4_files),
	'data_directory': data_directory,
	'processed_files': []
}

# Initialize lists to store combined data from all files
all_times = []
all_surface_temps = []
all_celsius_temps = []
all_readable_times = []

# Process each NC4 file
for i, nc4_file in enumerate(sorted(nc4_files)):
    print(f"\nProcessing file {i+1}/{len(nc4_files)}: {os.path.basename(nc4_file)}")
    
    try:
        data = Dataset(nc4_file, mode='r')
        
        # Print metadata for first file only
        if i == 0:
            print("MERRA-2 metadata from first file:")
            print(data)
        
        # Read in the temperature variable (T) and extract surface level:
        lons = data.variables['lon'][:]
        lats = data.variables['lat'][:]
        # Get temperature data - this is 4D: (time, lev, lat, lon)
        # Use masked arrays to handle any fill values or invalid data
        T_data = np.ma.filled(data.variables['T'][:,:,:,:], fill_value=np.nan)

        # Find nearest grid points (should be same for all files)
        lat_idx = np.argmin(np.abs(lats - target_lat))
        lon_idx = np.argmin(np.abs(lons - target_lon))

        # Get actual coordinates of nearest grid point (store from first file)
        if i == 0:
            actual_lat = float(lats[lat_idx])
            actual_lon = float(lons[lon_idx])
            out['metadata']['actual_coordinates'] = {'lat': actual_lat, 'lon': actual_lon}

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

        # Store pressure level info from first file
        if i == 0:
            out['metadata']['description'] = f'Surface temperature at human level ({surface_pressure} hPa pressure level)'
            out['pressure_level'] = float(surface_pressure)
            
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
            print(f"Warning: Issue reading time data from {nc4_file}: {e}")
            # Fallback: create a simple time index
            times = np.arange(len(time_var))
            
        time_units = getattr(time_var, 'units', 'unknown')
        
        # Store time units from first file
        if i == 0:
            out['time_units'] = time_units

        # Handle potential NaN values in surface temperature data
        surface_temps_clean = np.where(np.isfinite(surface_temperatures), surface_temperatures, None)
        
        # Add Celsius conversion for convenience
        celsius_temps = []
        for temp_k in surface_temps_clean:
            if temp_k is not None and np.isfinite(temp_k):
                celsius_temps.append(round(temp_k - 273.15, 2))  # Convert K to C and round to 2 decimals
            else:
                celsius_temps.append(None)

        # Add human-readable time stamps
        readable_times = []
        base_time = datetime.datetime(2015, 1, 2, 0, 0, 0)  # 2015-01-02 00:00:00
        for time_minutes in times:
            time_obj = base_time + datetime.timedelta(minutes=int(time_minutes))
            readable_times.append(time_obj.strftime('%Y-%m-%d %H:%M:%S UTC'))

        # Append data from this file to combined lists
        all_times.extend(times.tolist())
        all_surface_temps.extend(surface_temps_clean.tolist())
        all_celsius_temps.extend(celsius_temps)
        all_readable_times.extend(readable_times)
        
        # Track processed files
        out['metadata']['processed_files'].append({
            'filename': os.path.basename(nc4_file),
            'time_steps': len(times),
            'date_range': f"{readable_times[0]} to {readable_times[-1]}" if readable_times else "N/A"
        })
        
        data.close()
        print(f"  ✓ Processed {len(times)} time steps from {os.path.basename(nc4_file)}")
        
    except Exception as e:
        print(f"  ✗ Error processing {nc4_file}: {e}")
        continue

# Store combined data in output structure
out['times'] = all_times
out['surface_temperature_data'] = all_surface_temps
out['temperature_celsius'] = all_celsius_temps
out['time_readable'] = all_readable_times
out['total_time_steps'] = len(all_times)

with open('MERRA2_t2m.json', 'w', encoding='utf-8') as f:
	json.dump(out, f, indent=2)

print(f"\n✓ Successfully wrote MERRA2_t2m.json with data from {len(nc4_files)} files")
print(f"  Total time steps: {len(all_times)}")
print(f"  Date range: {all_readable_times[0] if all_readable_times else 'N/A'} to {all_readable_times[-1] if all_readable_times else 'N/A'}")
print("  JSON keys:", list(out.keys()))