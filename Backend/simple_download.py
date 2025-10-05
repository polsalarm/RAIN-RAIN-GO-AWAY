#!/usr/bin/env python3
"""
Simple MERRA2 Data Downloader
============================

A simplified version of the MERRA2 data download script.
"""

import os
import subprocess
import getpass
import shutil


def find_wget():
    """Find wget executable on the system."""
    wget_path = shutil.which('wget')
    if wget_path:
        return wget_path
    
    # On Windows, try common paths
    if os.name == 'nt':
        common_paths = [
            r'C:\Program Files\GnuWin32\bin\wget.exe',
            r'C:\Program Files (x86)\GnuWin32\bin\wget.exe',
            r'C:\Windows\System32\wget.exe',
            r'C:\tools\wget\wget.exe',
            r'C:\ProgramData\chocolatey\bin\wget.exe',
        ]
        for path in common_paths:
            if os.path.exists(path):
                return path
    
    return None


def main():
    """Simple download function."""
    print("MERRA2 Data Downloader")
    print("=" * 22)
    
    # Find wget
    wget_path = find_wget()
    if not wget_path:
        print("Error: wget not found!")
        print("Please install wget and make sure it's in your PATH.")
        return
    
    print(f"Using wget at: {wget_path}")
    
    # Get username
    username = input("Enter your NASA Earthdata username: ").strip()
    password = getpass.getpass("Enter your NASA Earthdata password: ")
    
    if not username or not password:
        print("Error: Username and password are required!")
        return
    
    # Find the URL file
    url_file = "data\\subset_M2I3NPASM_5.12.4_20251005_124626_.txt"
    if not os.path.exists(url_file):
        url_file = "subset_M2I3NPASM_5.12.4_20251005_124626_.txt"
        if not os.path.exists(url_file):
            print("Error: URL file not found!")
            return
    
    # Create cookies directory
    cookies_dir = os.path.expanduser("~\\.urs_cookies")
    os.makedirs(cookies_dir, exist_ok=True)
    cookies_file = os.path.join(cookies_dir, "cookies.txt")
    
    # Create download directory
    download_dir = "downloaded_data"
    os.makedirs(download_dir, exist_ok=True)
    
    # Build and run wget command using full path
    cmd = [
        wget_path,  # Use the actual wget path
        '--no-check-certificate',
        f'--load-cookies={cookies_file}',
        f'--save-cookies={cookies_file}',
        '--keep-session-cookies',
        f'--user={username}',
        f'--password={password}',
        f'--directory-prefix={download_dir}',
        '-i', url_file
    ]
    
    print(f"Downloading files to: {download_dir}")
    print("Starting download...")
    
    try:
        subprocess.run(cmd)
        print("Download completed!")
    except Exception as e:
        print(f"Error: {e}")


if __name__ == "__main__":
    main()