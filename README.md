# rpi-dashboard

![preview](https://github.com/M17-Project/rpi-dashboard/assets/44336093/b732959a-14dc-48cb-a6c0-7044aa2239c7)


# rpi-dashboard

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

These steps assume you’ve already installed and configured `m17-gateway` and nginx + PHP. For full-stack automation, check out the community installation script that sets up everything in one go, including firmware flash, NGINX, PHP‑FPM, and systemd services: [cc1200-hotspot-installer](https://github.com/DK1MI/cc1200-hotspot-installer).

```bash
git clone https://github.com/M17-Project/rpi-dashboard.git
cd rpi-dashboard
