✅ *Using XAMPP for Our Website*
✅ *MERRA2 Data Download Scripts*

All headings, tables, and code blocks are now Markdown-formatted for GitHub or any documentation platform.

---


# MERRA2 Data Download Scripts

## Using XAMPP for Our Website

This guide explains how to set up and run our website locally using **XAMPP**, a free and open-source cross-platform web server package that includes Apache, MySQL, PHP, and phpMyAdmin.

---

### 📂 Folder Overview

| File / Folder | Description |
|----------------|-------------|
| `/htdocs/` | Main web directory where our website files go |
| `/htdocs/index.php` | Entry point of the website |
| `/htdocs/config.php` | Configuration file for database connections |
| `/htdocs/assets/` | Static assets (CSS, JS, images, etc.) |
| `/htdocs/includes/` | Reusable PHP modules and scripts |

---

### 🧩 Prerequisites

#### 1. Install XAMPP
Download and install XAMPP from the official Apache Friends website:  
🔗 [https://www.apachefriends.org/download.html](https://www.apachefriends.org/download.html)

Choose the version compatible with your operating system:
- **Windows:** `.exe` installer  
- **macOS:** `.dmg` installer  
- **Linux:** `.run` installer

#### 2. Install a Text Editor (Recommended)
Use a modern code editor for easier configuration and PHP editing:
- [VS Code](https://code.visualstudio.com/)
- [Sublime Text](https://www.sublimetext.com/)
- [Atom](https://atom.io/)



### ⚙️ Setup Instructions

#### Option 1: Run the Website Locally (Recommended)

1. **Start XAMPP Control Panel**
   - Launch **XAMPP Control Panel**
   - Start **Apache** and **MySQL** services

2. **Copy the Website Files**
   Place your website folder inside:
```

C:\xampp\htdocs\

```
Example:
```

C:\xampp\htdocs\our_website\

````

3. **Create the Database**
- Open your browser and go to:  
  👉 [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
- Click **Databases → New**
- Enter a database name (e.g. `our_website_db`)
- Click **Create**

4. **Import the SQL File (if provided)**
- In phpMyAdmin, select your new database
- Go to the **Import** tab
- Choose the `.sql` file from the `database/` folder of the project
- Click **Go**

5. **Configure Database Connection**
- Open `config.php` (or `.env` file, if used)
- Update database credentials:
  ```php
  $servername = "localhost";
  $username = "root";
  $password = "";
  $dbname = "our_website_db";
  ```

6. **Run the Website**
- Open your browser and visit:  
  👉 [http://localhost/our_website](http://localhost/our_website)

#### Option 2: Access via Custom Port
If Apache is running on a different port (e.g. 8080):  
👉 [http://localhost:8080/our_website](http://localhost:8080/our_website)

You can change ports in **XAMPP Control Panel → Config → Apache (httpd.conf)**.


### 🔐 Security Notes

- **Never expose** your local XAMPP server to the public internet.  
- Keep `config.php` or `.env` files private (add them to `.gitignore`).  
- Use strong MySQL passwords in production environments.  
- For collaboration, share only the project files, not your `xampp` folder.



### 🧰 Troubleshooting

| Problem | Solution |
|----------|-----------|
| Apache won't start | Stop other services using port 80 (e.g. Skype, IIS), or change Apache port in XAMPP settings |
| MySQL won’t start | Check if port 3306 is already in use, or delete `ib_logfile*` files in the MySQL data directory |
| Website not loading | Ensure files are in `htdocs/our_website/` and Apache is running |
| Database connection error | Check `config.php` credentials and ensure MySQL is running |

---

### 🧑‍💻 For Team Collaboration

Each developer should:
1. Install XAMPP locally.  
2. Clone the project repository into their `htdocs` folder.  
3. Import the database file.  
4. Use their local setup for development.  

Add `database/` and `config.php` to `.gitignore` to prevent sharing sensitive data.



### 📞 Support

If you encounter issues:
- Verify Apache and MySQL are running  
- Check database configuration  
- Inspect the browser console for PHP or JS errors  
- Ask the team lead or system admin for help  

---

## MERRA2 Data Download Scripts

This folder contains Python scripts to download MERRA2 data from NASA Earthdata using your team's credentials.

---

### 📁 Files

- `download_merra2_data.py` - Full-featured download script with error handling and user-friendly interface  
- `simple_download.py` - Simplified version for quick downloads  
- `data/subset_M2I3NPASM_5.12.4_20251005_124626_.txt` - URL list file containing download links  

---

### 🧩 Prerequisites

#### 1. Install wget
**Windows:**
- Download wget from: [https://eternallybored.org/misc/wget/](https://eternallybored.org/misc/wget/)
- Or install via Chocolatey:
```bash
choco install wget
````

* Or install via Scoop:

  ```bash
  scoop install wget
  ```

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

#### 2. NASA Earthdata Account

You need a NASA Earthdata account to download the data:

* Register at: [https://urs.earthdata.nasa.gov/users/new](https://urs.earthdata.nasa.gov/users/new)
* Remember your username and password

---

### 🚀 Usage

#### Option 1: Full-featured script (Recommended)

```bash
python download_merra2_data.py
```

This script will:

* Check if wget is installed
* Prompt for your NASA Earthdata credentials
* Find the URL file automatically
* Create a download directory
* Handle cookies and authentication
* Resume partial downloads
* Provide detailed progress information

#### Option 2: Simple script

```bash
python simple_download.py
```

This is a minimal version that:

* Prompts for credentials
* Downloads files to `downloaded_data` folder
* Basic error handling

---

### 💡 What the Original Command Does

```bash
wget --no-check-certificate --load-cookies C:\.urs_cookies --save-cookies C:\.urs_cookies --keep-session-cookies --user=balbarosa31 --ask-password -i subset_M2I3NPASM_5.12.4_20251005_124626_.txt
```

* Downloads files listed in the URL file
* Handles NASA Earthdata authentication
* Saves session cookies for subsequent downloads
* Uses SSL without certificate verification (for compatibility)

---

### 🔐 Security Notes

* **Never commit credentials** to version control
* The scripts prompt for passwords securely (no echo)
* Cookies are stored in your home directory (`~/.urs_cookies/`)
* Each developer uses their own credentials

---

### 🧰 Troubleshooting

#### "wget not found" error

* Install wget using the instructions above
* Make sure wget is in your system PATH

#### Authentication errors

* Verify your NASA Earthdata credentials
* Check if your account has access to MERRA2 data
* Try logging in manually at [https://urs.earthdata.nasa.gov/](https://urs.earthdata.nasa.gov/)

#### Network issues

* The full script includes retry logic and timeouts
* You can resume interrupted downloads by running the script again

#### File permissions (Linux/macOS)

```bash
chmod +x download_merra2_data.py
chmod +x simple_download.py
```

---

### 🖥️ Example Output

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

---

### 👥 For Team Collaboration

Each developer should:

1. Get their own NASA Earthdata account
2. Install wget on their system
3. Run one of the Python scripts with their credentials

The downloaded files will be identical for everyone.
Consider adding `downloaded_data/` to `.gitignore` to avoid committing large data files.

---

### 📞 Support

If you encounter issues:

1. Check that wget is properly installed
2. Verify your NASA Earthdata credentials
3. Ensure you have internet connectivity
4. Check the console output for specific error messages

```

---

Would you like me to make this Markdown file downloadable as a ready-to-use `README.md` file? I can generate it and give you the link.
```
