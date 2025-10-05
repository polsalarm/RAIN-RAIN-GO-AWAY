
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

---

### 🔐 Security Notes

- **Never expose** your local XAMPP server to the public internet.  
- Keep `config.php` or `.env` files private (add them to `.gitignore`).  
- Use strong MySQL passwords in production environments.  
- For collaboration, share only the project files, not your `xampp` folder.

---

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

---

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
