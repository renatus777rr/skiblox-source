# Source code of SKIBLOX Website
Alright hello, talking owner of website of SKIBLOX. The old Roblox Launcher.
So the site is shutdown, it was for some days and for testers(which fucking unfriended me and fucked me. and plr stole source code before this github)
So here the private source code of skiblox.

# 1. Download all
https://www.apachefriends.org/ Download thing named XAMMP, it's an webserver with database.
# 2. Run up.
So, you run up as admin control panel.
# 3. Database setup (most hardest for new guys)
You go to localhost/phpmyadmin, then you create db with name skiblox. and just import the .sql in the phpmyadmin. Should be Done! You setted up the site. Go to http://localhost/ or localhost. You can now see the login. Enter SKIBLOX in the username, and password to skiblox. Then login. You in the owner account!
# 5. How to make admin and not admin?
Alright go to localhost/phpmyadmin and select database skiblox and you see under database skiblox the tables. select users table. You see this. <img width="1104" height="401" alt="Снимок экрана 2025-08-24 014144" src="https://github.com/user-attachments/assets/d13746ca-904d-4771-9a7b-563d04b4ab9f" /> Select the user by pressing Edit. Scroll down and then you should see <img width="651" height="43" alt="image" src="https://github.com/user-attachments/assets/47b9aa70-e29f-4f3e-b45a-a15a303e011d" /> Then if value 1, and you want un-admin then set it to 0 and save. If it value 0 then set it to 1, to set to admin and save. Done! Now you set up fully.

# 6. Setting up domain.
Alright this is step for who want the site up. You can use playit.gg server if you want, wtf???. Radmin vpn = hardest, need to set apache mode to online mode and edit something in httpd.conf require local to require all Granted. Cloudflare Tunnel: Of course bro, if you want real domain. local tunnel, Needs npm but still good. the site works on subdomains. Some other services to from localhost to random domain or subdomain: Yes also works but don't use bad ones.
hstn.me and rf.gd domain: THIS IS WHAT THE SKIBLOX WEBSITE USES ALWAYS. 24/7 hosting!!
# 7. Troubleshooting (only if you have problems)
Alright.
1. XAMPP doesn't start the server and says Apache: Stopped.
2. Answer: Download Microsoft Visual C++ 2013-2014 x86 and x64. If this doesnt help try Microsoft Visual C++ 2012 X86 X64.
3. It says DB connection failed.
4. Answer: go to htdocs folder and then open db.php with notepad. You should see: $DB_PASS = 'root'; and if you see instead of root something other, then set it to root. and save.
The full code should be good.
Thats all troubleshooting i had.

(BTW AI commented all code, for just knowing. bcs i was lazy to comment this shit. if you see something like ai edited, fix it or just make it normal as people do. XD)

visit skiblox now! skibloxrev.rf.gd



