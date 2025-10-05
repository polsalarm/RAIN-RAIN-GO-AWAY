#!/usr/bin/env python3
"""
Simple MERRA2 Data Downloader
============================

A simplified version of the MERRA2 data download script.
"""

import os
import subprocess
import getpass


def main():
    """Simple download function."""
    print("MERRA2 Data Downloader")
    print("=" * 22)
    
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
    
    # Build and run wget command
    cmd = [
        'wget',
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