# rpi-dashboard

![preview](/screenshot.jpg)

A lightweight web-based dashboard for managing and monitoring an **M17 hotspot** based on a **CC1200 HAT**, running on a Raspberry Pi.  
It pairs with the `m17-gateway` backend to provide real‑time status, logs, and control over the M17 hotspot.

---

## Features

- Displays **live logs** and status (connectivity, frequency, TX power, etc.)
- Simple **service control** for `m17-gateway` (start/stop/restart)
- Simple admin backend

The UI retrieves data by reading a JSON logfile produced by the m17-gateway.

---

## Prerequisites

- **Hardware**: Raspberry Pi (Zero, 3, 4, or newer) with a **CC1200 HAT**  
- **Backend**: `m17-gateway` service (from [jancona/m17](https://github.com/jancona/m17/tree/master/cmd/m17-gateway))  

---

## Installation

### Automatic Installation

These manual steps assume you’ve already installed and configured `m17-gateway` and NGINX + PHP. For full-stack automation, check out the community installation script that sets up everything in one go, including firmware flash, NGINX, PHP‑FPM, and systemd services: [cc1200-hotspot-installer](https://github.com/DK1MI/cc1200-hotspot-installer).

### Manual Installation

Install NGINX+PHP and `m17-gateway`. Make sure that the NGINX configuration looks like this:

```
$ sudo cat /etc/nginx/sites-enabled/default
server {
        listen 80 default_server;
        listen [::]:80 default_server;

        root /opt/m17/rpi-dashboard;

	index index.php index.html index.htm;

        server_name _;

        location / {
                try_files $uri $uri/ =404;
        }

	location ~ \.php$ {
		include snippets/fastcgi-php.conf;
		fastcgi_pass unix:/var/run/php/php-fpm.sock;
        }
}
```

Clone the dashboard to /opt/m17/rpi-dashboard:

```bash
cd /opt/m17
git clone https://github.com/M17-Project/rpi-dashboard.git
cd rpi-dashboard
```

Make sure to create symlinks for the files `dashboard.log` and `m17-gateway.ini` as NGINX is unable to access any files outside its document root. The following commands assume that your are using `/opt/m17/rpi-dashboard` as document root.

```bash
ln -s /opt/m17/m17-gateway/dashboard.log /opt/m17/rpi-dashboard/files/dashboard.log
ln -s /etc/m17-gateway.ini /opt/m17/rpi-dashboard/files/m17-gateway.ini
```

Add the user `www-data` to the group `m17-gateway-control`:

```bash
usermod -aG m17-gateway-control www-data
```

Please note that you will need to restart the Pi for the changes to take effect for the NGINX web server! Otherwise the dashboard will lack the necessary rights to access the required files on the filesystem.

Make sure that the group m17-gateway-control has
- read and write permissions on /etc/m17-gateway.ini
- read permissions on /opt/m17/m17-gateway/dashboard.log
- read and write permissions on /opt/m17/rpi-dashboard/files/M17Hosts.txt

This is how it should look like:

```bash
$ ls -l
total 8
lrwxrwxrwx 1 root root                  34 Jul 28 15:45 dashboard.log -> /opt/m17/m17-gateway/dashboard.log
lrwxrwxrwx 1 root root                  20 Jul 27 21:51 m17-gateway.ini -> /etc/m17-gateway.ini
-rwxrwxr-x 1 m17 m17-gateway-control  4753 Jul 26 12:20 M17Hosts.txt

$ ls -l /opt/m17/m17-gateway/dashboard.log
-rw-r--r-- 1 m17-gateway m17-gateway-control 44234 Jul 29 08:12 /opt/m17/m17-gateway/dashboard.log

$ ls -l /etc/m17-gateway.ini
-rw-rw-r-- 1 m17-gateway m17-gateway-control 400 Jul 28 23:00 /etc/m17-gateway.ini
```

Now navigate to the admin section of the rpi-dashboard and configure it as following:

- M17 Gateway Log File: files/dashboard.log
- M17 Gateway Configuration File: files/m17-gateway.ini

Also don't forget to update the M17 hosts file via the button in the admin interface.
