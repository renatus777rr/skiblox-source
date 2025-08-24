# Source code of SKIBLOX Website
Alright hello, talking owner of website of SKIBLOX. The old Roblox Launcher.
So the site is shutdown, it was for some days and for testers(which fucking unfriended me and fucked me. and plr stole source code before this github)
So here the private source code of skiblox.

# 1. Download all
yes Download all before setting up.
Download the source code and then UWAMP. from this site: https://www.uwamp.com/en/ thanks them to host the site.

# 2. Run up.
Go to the uwamp root directory (for default C:\UwAmp) and then go to www. Dont delete anything from there except index. Then copy all files from the zip of source code to there. Done.
Now start the whole server. Make sure you have Visual C++ 2013-2014 and both x86-64. And then start the server. 

# 3. Database setup (most hardest for new guys)
Alright the most hardest step. Go to "http://localhost/mysql" then enter in username root, password root. and then log in. Go to the databases tab and then enter database name to "skiblox" and then create. select the database in left list like this <img width="235" height="51" alt="image" src="https://github.com/user-attachments/assets/24d462a3-4b06-4d27-beb5-ae9949f101f9" /> and then after selecting go to import tab
 <img width="840" height="68" alt="Снимок экрана 2025-08-24 013147" src="https://github.com/user-attachments/assets/b1afebcd-2ab2-4118-a4e3-1c782d3476b9" />
then select the .sql file that was with you in the source code. skiblox.sql. After selectin scroll down and select "Go". Wait until done and you should see in the selected database this: <img width="183" height="90" alt="image" src="https://github.com/user-attachments/assets/da0affd1-5ca2-41c4-95f1-16ef0b415b63" /> If yes then continue, if no then check all steps, did you do right. if nothing still happens then contact me (discord notcopilot). After doing this close site and lets go to the setup of PHP.
# 4. Last steps, selecting php version.
Alright go to the uwamp app then you see this: <img width="267" height="32" alt="image" src="https://github.com/user-attachments/assets/f825d7fe-a975-43f6-bf13-4a4937640633" /> If you don't have the PHP 7.2.7 then lets go download it!. Press on that green puzzle showed on screenshot. <img width="524" height="449" alt="image" src="https://github.com/user-attachments/assets/b5d31975-339e-4f9a-bbb4-27e36b501d88" /> You see this. Select from repository list "UwAmp PHP Repository" then scroll down and check this checkbox <img width="204" height="21" alt="image" src="https://github.com/user-attachments/assets/01a9544c-b654-4a00-809c-54834f8992b4" /> and then press down Install button. then wait and restart server. Done! You setted up the site. Go to http://localhost/ or localhost. You can now see the login. Enter SKIBLOX in the username, and password to skiblox. Then login. You in the owner account!
#. 5. How to make admin and not admin?
Alright go to localhost/mysql and select database skiblox and you see under database skiblox the tables. select users table. You see this. <img width="1104" height="401" alt="Снимок экрана 2025-08-24 014144" src="https://github.com/user-attachments/assets/d13746ca-904d-4771-9a7b-563d04b4ab9f" /> Select the user by pressing Edit. Scroll down and then you should see <img width="651" height="43" alt="image" src="https://github.com/user-attachments/assets/47b9aa70-e29f-4f3e-b45a-a15a303e011d" /> Then if value 1, and you want un-admin then set it to 0 and save. If it value 0 then set it to 1, to set to admin and save. Done! Now you set up fully.

# 6. Troubleshooting (only if you have problems)
Alright.
1. UwAmp doesn't start the server and says Apache: Stopped.
2. Answer: Download Microsoft Visual C++ 2013-2014 x86 and x64. If this doesnt help try Microsoft Visual C++ 2012 X86 X64. If nothing still you need install old uwamp version then.
3. It says DB connection failed.
4. Answer: go to WWW folder and then open db.php with notepad. You should see: $DB_PASS = 'root'; and if you see instead of root, something other. then set it to root. and save.
The full code should be:

<?php
// db.php
declare(strict_types=1);

$DB_HOST = 'localhost';
$DB_NAME = 'skiblox';
$DB_USER = 'root';
$DB_PASS = 'root'; 

$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];

try {
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
  http_response_code(500);
  exit('DB connection failed.');
}
Thats all troubleshooting i had.


(BTW AI commented all code, for just knowing. bcs i was lazy to comment this shit. if you see something like ai edited, fix it or just make it normal as people do. XD)





# 
