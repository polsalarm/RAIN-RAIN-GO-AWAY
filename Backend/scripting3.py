import os
os.environ["HDF5_USE_FILE_LOCKING"] = "FALSE"
import xarray as xr
import numpy as np
from datetime import datetime
import requests
from requests.adapters import HTTPAdapter
from urllib3.util.retry import Retry

# --- User Inputs ---
lat_input = float(input("Enter latitude (e.g., 14.6): "))
lon_input = float(input("Enter longitude (e.g., 121.0): "))
date_input = input("Enter date (YYYY-MM-DD, e.g., 2025-10-05): ")
hour_input = int(input("Enter hour (0-23, UTC): "))

# --- Bearer token for GES DISC ---
token = "BREARER_TOKEN_HERE"
# --- Construct date parts ---
date_obj = datetime.strptime(date_input, "%Y-%m-%d")
yyyy, mm, dd = date_obj.strftime("%Y"), date_obj.strftime("%m"), date_obj.strftime("%d")

# --- List of product numbers to try ---
product_numbers = ["100", "200", "300", "400"]

# --- Setup requests session with retries ---
session = requests.Session()
retries = Retry(total=5, backoff_factor=0.5)
session.mount("https://", HTTPAdapter(max_retries=retries))
session.headers.update({"Authorization": f"Bearer {token}"})

ds = None
for product in product_numbers:
    url = f"https://goldsmr4.gesdisc.eosdis.nasa.gov/opendap/MERRA2/M2T1NXSLV.5.12.4/{yyyy}/{mm}/MERRA2_{product}.tavg1_2d_slv_Nx.{yyyy}{mm}{dd}.nc4"
    print(f"Trying: {url}")
    try:
        ds = xr.open_dataset(
            url,
            engine="pydap",  # use pydap to support requests session
            backend_kwargs={"session": session}
        )
        print(f"✅ Dataset found: MERRA2_{product}")
        break
    except Exception as e:
        print(f"❌ Could not open MERRA2_{product}: {e}")

if ds is None:
    print("❌ None of the MERRA2 datasets were found for this date.")
    exit()

# --- Find nearest grid point ---
lat_idx = np.abs(ds['lat'] - lat_input).argmin()
lon_idx = np.abs(ds['lon'] - lon_input).argmin()

# --- Extract variables ---
T2M = ds['T2M'][:, lat_idx, lon_idx] - 273.15  # Kelvin to Celsius
U10M = ds['U10M'][:, lat_idx, lon_idx]
V10M = ds['V10M'][:, lat_idx, lon_idx]

# --- Convert times ---
datetimes = ds['time'].values

# --- Hour selection and analysis ---
if 0 <= hour_input < len(datetimes):
    # Point-in-time
    t_temp = T2M[hour_input].values
    u_wind = U10M[hour_input].values
    v_wind = V10M[hour_input].values
    wind_speed = np.sqrt(u_wind**2 + v_wind**2)

    # Daily summary
    day_temp_min = float(T2M.min().values)
    day_temp_max = float(T2M.max().values)
    day_temp_avg = float(T2M.mean().values)

    day_wind_min = float(np.sqrt(U10M.values**2 + V10M.values**2).min())
    day_wind_max = float(np.sqrt(U10M.values**2 + V10M.values**2).max())
    day_wind_avg = float(np.sqrt(U10M.values**2 + V10M.values**2).mean())

    # --- Classification thresholds (example values, can be tuned) ---
    verdicts = []
    if t_temp > 35:
        verdicts.append("very hot")
    elif t_temp < 10:
        verdicts.append("very cold")

    if wind_speed > 10:  # m/s (~36 km/h)
        verdicts.append("very windy")

    # MERRA2 SLV dataset doesn’t directly include rainfall,
    # but we could check relative humidity (QV2M or RH2M) if available.
    if "QV2M" in ds.variables:
        humidity = ds["QV2M"][hour_input, lat_idx, lon_idx].values * 1000  # g/kg approx
        if humidity > 15:  # approximate threshold
            verdicts.append("very wet")
    else:
        humidity = None

    if (t_temp > 30 and humidity and humidity > 12) or (wind_speed > 12):
        verdicts.append("very uncomfortable")

    # --- Output Section ---
    print("\n📍 Weather Information Report")
    print("================================")
    print(f"Location: {lat_input:.5f}°N, {lon_input:.5f}°E")
    print(f"Date & Hour (UTC): {date_input} {hour_input:02d}:00")
    print("--------------------------------")

    # Point-in-time conditions
    print("⏱️ Conditions at chosen time:")
    print(f"   🌡️ Temperature: {t_temp:.2f} °C")
    print(f"   🌬️ Wind speed: {wind_speed:.2f} m/s")
    if humidity is not None:
        print(f"   💧 Humidity (QV2M approx.): {humidity:.1f} g/kg")

    # Daily summary
    print("\n📊 Daily Summary:")
    print(f"   🌡️ Temperature: min {day_temp_min:.2f} °C | max {day_temp_max:.2f} °C | avg {day_temp_avg:.2f} °C")
    print(f"   🌬️ Wind speed: min {day_wind_min:.2f} m/s | max {day_wind_max:.2f} m/s | avg {day_wind_avg:.2f} m/s")

    # Final verdict
    print("\n📝 Final Verdict:")
    if verdicts:
        print(f"   Conditions are likely: {', '.join(verdicts)}.")
    else:
        print("   ✅ Conditions appear favorable. Not extreme.")

    # Recommendations
    print("\n💡 Recommendations:")
    if "very hot" in verdicts:
        print("   - Stay hydrated, avoid strenuous outdoor activity in midday.")
    if "very cold" in verdicts:
        print("   - Dress warmly, consider rescheduling if prolonged exposure.")
    if "very windy" in verdicts:
        print("   - Secure loose objects, small boats and fishing trips may be risky.")
    if "very wet" in verdicts:
        print("   - Bring rain gear or consider indoor alternatives.")
    if "very uncomfortable" in verdicts:
        print("   - Conditions may be stressful (heat + humidity or strong winds). Limit outdoor exposure.")
    if not verdicts:
        print("   - Great day for outdoor activities like hiking, fishing, or relaxing outside.")

else:
    print("⚠️ Invalid hour. Must be between 0 and 23.")
