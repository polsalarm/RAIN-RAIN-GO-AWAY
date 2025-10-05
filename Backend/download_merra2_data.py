#!/usr/bin/env python3
"""
MERRA2 Data Download Script
===========================

This script downloads MERRA2 data files using wget with NASA Earthdata credentials.
It provides a user-friendly interface for co-developers to download the data with their own credentials.

Requirements:
- wget installed on the system
- NASA Earthdata account credentials
- Internet connection

Usage:
    python download_merra2_data.py
"""

import os
import sys
import subprocess
import getpass
from pathlib import Path


def check_wget_availability():
    """Check if wget is available on the system."""
    try:
        subprocess.run(['wget', '--version'], 
                      capture_output=True, check=True)
        return True
    except (subprocess.CalledProcessError, FileNotFoundError):
        return False


def get_user_credentials():
    """Get NASA Earthdata credentials from user input."""
    print("NASA Earthdata Login Required")
    print("=" * 35)
    print("Please enter your NASA Earthdata credentials.")
    print("If you don't have an account, register at: https://urs.earthdata.nasa.gov/users/new")
    print()
    
    username = input("Enter your NASA Earthdata username: ").strip()
    if not username:
        print("Error: Username cannot be empty.")
        return None, None
    
    password = getpass.getpass("Enter your NASA Earthdata password: ")
    if not password:
        print("Error: Password cannot be empty.")
        return None, None
    
    return username, password


def setup_cookies_directory():
    """Setup the cookies directory and return the path."""
    cookies_dir = Path.home() / ".urs_cookies"
    cookies_dir.mkdir(exist_ok=True)
    return cookies_dir / "cookies.txt"


def find_url_file():
    """Find the URL file in the current directory or data subdirectory."""
    current_dir = Path.cwd()
    
    # Look for files matching the pattern
    url_files = list(current_dir.glob("subset_M2I3NPASM_*.txt"))
    if url_files:
        return url_files[0]
    
    # Check in data subdirectory
    data_dir = current_dir / "data"
    if data_dir.exists():
        url_files = list(data_dir.glob("subset_M2I3NPASM_*.txt"))
        if url_files:
            return url_files[0]
    
    return None


def create_download_directory():
    """Create and return the download directory path."""
    download_dir = Path.cwd() / "downloaded_data"
    download_dir.mkdir(exist_ok=True)
    return download_dir


def run_wget_command(username, password, url_file, cookies_file, download_dir):
    """Execute the wget command to download MERRA2 data."""
    
    # Construct the wget command
    cmd = [
        'wget',
        '--no-check-certificate',
        f'--load-cookies={cookies_file}',
        f'--save-cookies={cookies_file}',
        '--keep-session-cookies',
        f'--user={username}',
        f'--password={password}',
        '--directory-prefix=' + str(download_dir),
        '--continue',  # Resume partial downloads
        '--timeout=30',  # Set timeout
        '--tries=3',  # Number of retries
        '-i', str(url_file)
    ]
    
    print(f"\nStarting download to: {download_dir}")
    print("This may take a while depending on file sizes and internet speed...")
    print("Press Ctrl+C to cancel if needed.\n")
    
    try:
        # Run the command
        result = subprocess.run(cmd, check=False)
        return result.returncode == 0
    except KeyboardInterrupt:
        print("\nDownload cancelled by user.")
        return False
    except Exception as e:
        print(f"Error running wget: {e}")
        return False


def main():
    """Main function to orchestrate the download process."""
    print("MERRA2 Data Download Tool")
    print("=" * 25)
    print()
    
    # Check if wget is available
    if not check_wget_availability():
        print("Error: wget is not installed or not available in PATH.")
        print("Please install wget first:")
        print("- On Windows: Install wget using Chocolatey, Scoop, or download from https://eternallybored.org/misc/wget/")
        print("- On macOS: brew install wget")
        print("- On Linux: apt-get install wget or yum install wget")
        sys.exit(1)
    
    # Find the URL file
    url_file = find_url_file()
    if not url_file:
        print("Error: Could not find the URL file (subset_M2I3NPASM_*.txt)")
        print("Please ensure the file is in the current directory or 'data' subdirectory.")
        sys.exit(1)
    
    print(f"Found URL file: {url_file}")
    
    # Get user credentials
    username, password = get_user_credentials()
    if not username or not password:
        print("Error: Valid credentials are required.")
        sys.exit(1)
    
    # Setup cookies file
    cookies_file = setup_cookies_directory()
    print(f"Cookies will be stored in: {cookies_file}")
    
    # Create download directory
    download_dir = create_download_directory()
    
    # Confirm before starting
    print(f"\nDownload Configuration:")
    print(f"- Username: {username}")
    print(f"- URL file: {url_file}")
    print(f"- Download directory: {download_dir}")
    print(f"- Cookies file: {cookies_file}")
    print()
    
    confirm = input("Proceed with download? (y/N): ").strip().lower()
    if confirm not in ['y', 'yes']:
        print("Download cancelled.")
        sys.exit(0)
    
    # Run the download
    success = run_wget_command(username, password, url_file, cookies_file, download_dir)
    
    if success:
        print("\n✓ Download completed successfully!")
        print(f"Files have been downloaded to: {download_dir}")
    else:
        print("\n✗ Download failed or was incomplete.")
        print("Check the error messages above for details.")
        print("You can run this script again to resume partial downloads.")


if __name__ == "__main__":
    try:
        main()
    except Exception as e:
        print(f"Unexpected error: {e}")
        sys.exit(1)