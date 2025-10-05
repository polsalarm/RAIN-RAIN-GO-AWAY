# MERRA2 Data Download Scripts

This folder contains Python scripts to download MERRA2 data from NASA Earthdata using your team's credentials.

## Files

- `download_merra2_data.py` - Full-featured download script with error handling and user-friendly interface
- `simple_download.py` - Simplified version for quick downloads
- `data/subset_M2I3NPASM_5.12.4_20251005_124626_.txt` - URL list file containing download links

## Prerequisites

### 1. Install wget
**Windows:**
- Download wget from: https://eternallybored.org/misc/wget/
- Or install via Chocolatey: `choco install wget`
- Or install via Scoop: `scoop install wget`

**macOS:**
```bash
brew install wget
```

**Linux:**
```bash
# Ubuntu/Debian
sudo apt-get install wget

# CentOS/RHEL
sudo yum install wget
```

### 2. NASA Earthdata Account
You need a NASA Earthdata account to download the data:
- Register at: https://urs.earthdata.nasa.gov/users/new
- Remember your username and password

## Usage

### Option 1: Full-featured script (Recommended)
```bash
python download_merra2_data.py
```

This script will:
- Check if wget is installed
- Prompt for your NASA Earthdata credentials
- Find the URL file automatically
- Create a download directory
- Handle cookies and authentication
- Resume partial downloads
- Provide detailed progress information

### Option 2: Simple script
```bash
python simple_download.py
```

This is a minimal version that:
- Prompts for credentials
- Downloads files to `downloaded_data` folder
- Basic error handling

## What the original command does

The original wget command:
```bash
wget --no-check-certificate --load-cookies C:\.urs_cookies --save-cookies C:\.urs_cookies --keep-session-cookies --user=balbarosa31 --ask-password -i subset_M2I3NPASM_5.12.4_20251005_124626_.txt
```

- Downloads files listed in the URL file
- Handles NASA Earthdata authentication
- Saves session cookies for subsequent downloads
- Uses SSL without certificate verification (for compatibility)

## Security Notes

- **Never commit credentials to version control**
- The scripts prompt for passwords securely (no echo)
- Cookies are stored in your home directory (`~/.urs_cookies/`)
- Each developer uses their own credentials

## Troubleshooting

### "wget not found" error
- Install wget using the instructions above
- Make sure wget is in your system PATH

### Authentication errors
- Verify your NASA Earthdata credentials
- Check if your account has access to MERRA2 data
- Try logging in manually at https://urs.earthdata.nasa.gov/

### Network issues
- The full script includes retry logic and timeouts
- You can resume interrupted downloads by running the script again

### File permissions (Linux/macOS)
```bash
chmod +x download_merra2_data.py
chmod +x simple_download.py
```

## Example Output

```
MERRA2 Data Download Tool
=========================

Found URL file: data\subset_M2I3NPASM_5.12.4_20251005_124626_.txt

NASA Earthdata Login Required
===================================
Please enter your NASA Earthdata credentials.
If you don't have an account, register at: https://urs.earthdata.nasa.gov/users/new

Enter your NASA Earthdata username: your_username
Enter your NASA Earthdata password: [hidden]

Download Configuration:
- Username: your_username  
- URL file: data\subset_M2I3NPASM_5.12.4_20251005_124626_.txt
- Download directory: downloaded_data
- Cookies file: C:\Users\YourName\.urs_cookies\cookies.txt

Proceed with download? (y/N): y

Starting download to: downloaded_data
This may take a while depending on file sizes and internet speed...

✓ Download completed successfully!
Files have been downloaded to: downloaded_data
```

## For Team Collaboration

1. Each developer should:
   - Get their own NASA Earthdata account
   - Install wget on their system
   - Run one of the Python scripts with their credentials

2. The downloaded files will be identical for everyone
3. Consider adding `downloaded_data/` to `.gitignore` to avoid committing large data files

## Support

If you encounter issues:
1. Check that wget is properly installed
2. Verify your NASA Earthdata credentials
3. Ensure you have internet connectivity
4. Check the console output for specific error messages