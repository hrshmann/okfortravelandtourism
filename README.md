# OK FOR TRAVEL AND TOURISM CO — Website & Travel Engine

A professional travel website with a live flight search engine, editable holiday packages,
an admin panel, and WhatsApp booking for every product.

## Quick start (Windows)

1. Install Node.js 18+ (already installed on this PC).
2. Double-click **`start.bat`**, or run:
   ```
   node server.js
   ```
3. Open **http://localhost:8000** for the website and **http://localhost:8000/admin** for the admin panel.
   Default admin password: `admin123`. **Change it** (see Configuration).

No `npm install` is needed. The server only uses Node's standard library.

## Features

| Area | What it does |
|---|---|
| Flight engine | Airport autocomplete (90+ airports), one-way or return trips, travellers and cabin class, filters (stops, price, departure time, airline), sorting (cheapest, fastest, earliest), airline logos |
| Packages | Category chips, search and sort, a detail modal with inclusions and a day-by-day itinerary, and a booking form with a live total |
| WhatsApp | Every flight, package, service, enquiry form and the chat widget opens `wa.me/<number>` with a pre-filled message |
| Enquiry log | Every WhatsApp click is saved to `data/enquiries.json` and listed in the admin panel |
| Admin | Add, edit, duplicate, delete, reorder and publish/hide packages; image upload; JSON import/export; company settings (WhatsApp number, phone, currency, hero text, social links, stats) |
| Design | Responsive from phones to desktops, scroll animations, counters, a testimonials carousel and an FAQ accordion |

## Configuration

Copy `.env.example` to `.env` and edit it:

```
ADMIN_PASSWORD=your-strong-password
FLIGHT_PROVIDER=auto
DUFFEL_ACCESS_TOKEN=
TRAVELPAYOUTS_TOKEN=
TRAVELPAYOUTS_MARKER=
```

Set the **WhatsApp number** in Admin → Settings. Use international format with digits only, e.g. `971501234567`.
Then press **Test WhatsApp link** to check it.

## Free flight APIs

Without a key, the engine shows **sample fares** so the site works straight away.
To show real fares, connect one of these free APIs:

### Option A: Travelpayouts / Aviasales (free, and you earn commission)
1. Sign up at https://www.travelpayouts.com and join the **Aviasales** programme.
2. Go to **Tools → API** and copy your **API token**. Your **marker** (partner ID) is in your profile.
3. Put both in `.env` as `TRAVELPAYOUTS_TOKEN` and `TRAVELPAYOUTS_MARKER`, then restart the server.
4. The fares are real but cached prices. Each result gets a **Book online** button that links to Aviasales
   with your affiliate marker, next to **Book on WhatsApp**.

### Option B: Duffel (free test mode with real airline schedules)
1. Sign up at https://duffel.com, open **Developers → Access tokens**, and create a **test** token (`duffel_test_…`).
2. Put it in `.env` as `DUFFEL_ACCESS_TOKEN`, then restart the server.
3. Test mode is free. To take payments through Duffel and issue tickets, switch to a live token (Duffel charges per booking).

`FLIGHT_PROVIDER=auto` uses Duffel if its token is set, then Travelpayouts, then sample fares.
If a live API fails, the site falls back to sample fares and says so on the results page.

## Project structure

```
server.js            Node.js web server + REST API (packages, settings, enquiries, flights, airports)
package.json         Entry point and start command for Node.js hosting
.env.example         Configuration template
start.bat            Double-click launcher for Windows
data/
  packages.json      Holiday packages (edited from the admin panel)
  settings.json      Company details and homepage content
  airports.json      Airport list for autocomplete
  enquiries.json     Created automatically when visitors click WhatsApp
public/
  index.html         Website
  admin.html         Admin panel
  css/style.css      Styles
  js/app.js          Website logic (search engine, packages, WhatsApp)
  js/admin.js        Admin panel logic
  js/icons.js        Icon set
```

## Going live

### Hostinger (Node.js web app)

1. In hPanel, add a **Node.js** web app for your domain (available on Business and Cloud plans).
2. Upload this folder as a ZIP (or connect its GitHub repo). Leave out `.env` and `start.bat`.
3. Choose Node.js **18 or newer**, entry file **`server.js`** and start command `npm start`. There is no build step.
4. Under **Environment variables**, add `ADMIN_PASSWORD` and any flight API keys from `.env.example`.
   Don't set `PORT` or `HOST`: the server uses the port Hostinger provides.
5. Deploy, then open your domain and `/admin`.

**Back up `data/` before every redeploy.** Packages, settings and enquiries are saved there, and uploading
the project again replaces those files with the copies in the upload. Download `data/` from File Manager
first, or upload a ZIP without the `data/` folder after the first deploy.

### Any other server

- Host it on any VPS (Hostinger VPS, DigitalOcean, AWS Lightsail, and so on) that has Node.js 18+ installed.
- Set `HOST=0.0.0.0` and `PORT=80` in `.env` (or keep 8000 and run it behind Nginx with HTTPS).
- Keep the server running with `systemd`, `pm2 start server.js`, or NSSM on Windows.
- Back up the `data/` folder regularly. It holds all your packages, settings and enquiries.
- Always change `ADMIN_PASSWORD` before going live.
