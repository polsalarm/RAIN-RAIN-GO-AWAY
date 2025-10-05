# Import the required Python libraries. They are used to read and plot the data. If any of the following import commands fail, check the local Python environment and install any missing packages.

import json
import numpy as np
from netCDF4 import Dataset

# Read in NetCDF4 file (add a directory path if necessary):

data = Dataset('MERRA2_300.tavgM_2d_slv_Nx.201001.nc4', mode='r')

# Run the following line below to print MERRA-2 metadata. This line will print attribute and variable information. From the 'variables(dimensions)' list, choose which variable(s) to read in below.
print(data)

# Read in the 'T2M' 2-meter air temperature variable:
lons = data.variables['lon'][:]
lats = data.variables['lat'][:]
T2M = data.variables['T2M'][:,:,:]

# If using MERRA-2 data with multiple time indices in the file, the following line will extract only the first time index.
# Note: Changing T2M[0,:,:] to T2M[10,:,:] will subset to the 11th time index.

T2M = T2M[0,:,:]



# Instead of plotting, write the data and minimal metadata to a JSON file.
out = {}
out['metadata'] = {
	'title': 'MERRA-2 Air Temperature at 2m, January 2010',
	'variable': 'T2M'
}

# try to capture units if present
t2m_var = data.variables.get('T2M')
if t2m_var is not None:
	units = getattr(t2m_var, 'units', None)
	if units is not None:
		out['metadata']['units'] = units

# Convert arrays to plain Python lists for JSON serialization
out['lons'] = np.asarray(lons).tolist()
out['lats'] = np.asarray(lats).tolist()
out['T2M'] = np.asarray(T2M).tolist()

with open('MERRA2_t2m.json', 'w', encoding='utf-8') as f:
	json.dump(out, f, indent=2)

print("Wrote MERRA2_t2m.json with keys:", list(out.keys()))