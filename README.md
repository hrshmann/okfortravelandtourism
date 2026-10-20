# OK Travel & Tourism — website and admin panel

A complete travel-agency website with a built-in CMS and sales CRM. It is written in plain PHP + MySQL, so it runs on any Hostinger plan (Premium, Business or Cloud) with no build step, Composer or Node.

- **Public website:** a homepage with a live split-flap "Departures" board, holiday packages, destination guides, a flight search engine, Umrah & Hajj, visa services, a travel journal, FAQ, contact, and a Track-your-request page.
- **Admin panel** (`/admin`):
  - **Leads & bookings CRM:** pipeline, notes, follow-ups, quotations and invoices, CSV export.
  - **Content:** packages, destinations, visas, articles, reviews, FAQs, pages and a media library.
  - **Business:** team accounts with roles, settings, an activity log and backups.

---

## 1. Install on Hostinger (about 10 minutes)

**Before you start:** you need the `ok-travel-website.zip` file, your Hostinger login, and the domain connected to your hosting plan.

1. **Create the database.**
   - Go to hPanel → **Databases → MySQL Databases**.
   - Create a database and a user with a strong password, and copy the database name, user name and password. They look like `u123456789_oktravel`.
2. **Upload the files.**
   - Go to hPanel → **Files → File Manager** and open `public_html`.
   - Delete the default `default.php` or `index.html`.
   - Upload `ok-travel-website.zip`, right-click it and choose **Extract** into `public_html`. Then delete the zip.
   - Make sure the files sit directly in `public_html`, not in a subfolder: you should see `index.php`, `app/`, `assets/` and `uploads/`.
3. **Check the PHP version.** In hPanel → **Advanced → PHP Configuration**, choose **PHP 8.1 or newer** (8.2 recommended). The default extensions are enough.
4. **Run the installer.**
   - Open your domain in a browser. You are sent to `/install` automatically.
   - Choose **MySQL**, use host `localhost`, and enter the database details from step 1.
   - Create your administrator account: your email and a password of at least 10 characters with letters and numbers.
   - Press **Install website**. You land in the admin panel with the starter content loaded.
5. **Turn on SSL.**
   - In hPanel → **Security → SSL**, install the free certificate if it isn't active yet.
   - Then go to **Admin → Settings → System** and switch on **Always use HTTPS**.

The installer locks itself after it runs. To reinstall, delete `app/config.php` and empty the database.

> **Subfolder installs** (for example `example.com/travel`): extract into that folder and change `RewriteBase /` to `RewriteBase /travel/` in `.htaccess`.

---

## 2. Before you announce the site

The dashboard shows a **Get ready to launch** checklist. In short:

| Where | What to do |
|---|---|
| Settings → Contact | Your real **WhatsApp number** (digits only, e.g. `971501234567`), phone, email, address and office hours. Press **Test it** next to the WhatsApp field. |
| Settings → Business | Company name, **trade/tourism licence number**, currency (USD, AED, SAR, PKR…). |
| Settings → Email | SMTP details so lead alerts and customer confirmations arrive (see below), then **Send a test email**. |
| Reviews | **Delete the five sample reviews** and add real ones from your customers. |
| Settings → Homepage | Check the four homepage numbers are true for your business. |
| Packages / Visas | Review prices, inclusions and photos. Replace stock photos with your own when you can. |
| Pages | Read the Privacy policy, Terms and Refund policy and adapt them to your business. |
| Settings → SEO | Add your domain, Google Analytics 4 ID and Search Console code. Submit `https://yourdomain.com/sitemap.xml` in Search Console. |

### Email (SMTP) on Hostinger
1. In hPanel → **Emails**, create a mailbox such as `bookings@yourdomain.com`.
2. In **Admin → Settings → Email**, enter:
   - Host: `smtp.hostinger.com`
   - Port: `465`
   - Security: **SSL**
   - Username: the full mailbox address
   - Password: the mailbox password
3. Set **From address** to the same mailbox.
4. Press **Send a test email**.

Without SMTP, the site falls back to PHP mail, which often lands in spam.

---

## 3. How the sales flow works

1. A visitor sends a request: from a package page, the homepage Umrah/Visa tabs, the contact page, or any destination or visa page.
2. The request is saved under a reference number such as `OK2610-7KQ4M`.
3. Your team gets an email alert. The customer gets a confirmation email if they gave an email address.
4. The visitor's thank-you screen offers **Continue on WhatsApp**, with the reference pre-filled, so chats can be matched to leads.
5. In **Leads & bookings**, your team:
   - assigns the lead and moves it through the pipeline: *New → Contacted → Quoted → Confirmed → Travelled* (or *Cancelled / Lost*);
   - records the quoted and paid amounts;
   - adds call notes and sets a follow-up date, which appears on the dashboard;
   - prints a **quotation** or **invoice**, or saves it as PDF.
6. Customers can check progress any time at `/track` with their reference and phone or email.

WhatsApp button clicks are also logged, in their own **WhatsApp clicks** tab, so you can see what people asked about even when they never filled in a form.

### Team roles

| Role | Can do |
|---|---|
| Sales agent | Leads only |
| Content editor | Leads plus all website content and media |
| Administrator | Everything, including team accounts, settings and backups |

---

## 4. Managing content

- **Packages:**
  - Drag rows to set the order.
  - Use the switches to publish/hide or feature a package.
  - **Duplicate** a package to create a similar one quickly.
  - Featured packages appear first on the homepage and the Departures board. Set each package's *Airport code* and *Board status* for the board.
- **Images:**
  - Upload from any image field or from the **Media library**.
  - Photos are resized to 2000px and converted to WebP automatically.
- **Rich text:** the editor supports headings, lists, links, quotes and images. Pasted text is cleaned of hidden formatting.
- **SEO:** each package, destination, visa, article and page has optional SEO title and description fields. The sitemap and structured data (TravelAgency, Product/TouristTrip, BlogPosting, FAQPage, Breadcrumbs) are generated automatically.

### Currencies (INR, AED, EUR …)

Prices are entered **once, in your main currency**. Visitors can show them in any currency you switch on, using the switcher in the header. On phones it's in the menu.

Go to **Admin → Settings → Currencies**:
- **Main currency:** the currency you type prices in (USD, AED, INR, SAR …).
- **Which currencies to offer:** tick the ones you want. 15 are available: USD, AED, EUR, GBP, INR, SAR, PKR, QAR, OMR, KWD, BHD, BDT, LKR, MYR and TRY.
- **Exchange rates:**
  - Rates update automatically once a day, free from open.er-api.com, or you can press **Update rates now**.
  - To set your own rates, turn off automatic updates and type them in.
  - Rates are shown as "1 main currency = …".
- **Safety margin:** an optional % added to converted prices, to cover exchange-rate movement. Prices in your main currency never change.
- **Automatic currency:** first-time visitors see the currency of their country (India → ₹, UAE → AED, Saudi Arabia → SAR, UK → £, Europe → €).

How converted prices appear:
- They're rounded up to a tidy figure, e.g. ₹74,617 → ₹74,700.
- Indian, Pakistani and Bangladeshi prices use lakh grouping (₹1,80,000).
- They're marked "approximate" on package and visa pages.
- Leads record which currency the customer was viewing.
- Quotations and invoices are always issued in your main currency.

---

## 5. Live flight fares (optional)

Without an API key, the flight search shows clearly labelled **sample fares**. Every result offers *Book on WhatsApp*, and a consultant confirms the real price.

To show real fares, go to **Admin → Settings → Flight search**:
- **Travelpayouts / Aviasales** (free, and you earn commission):
  1. Sign up at travelpayouts.com and join the Aviasales programme.
  2. Paste the API token and your marker (partner ID).
  3. Results get a **Book online** link that carries your affiliate marker.
- **Duffel:** real-time airline offers. Paste an access token from duffel.com (test tokens are free).

If a provider is down, the search falls back to sample fares and says so to the visitor.

---

## 6. Backups and maintenance

- **Admin → Backup → Download backup** saves all data (packages, leads, settings…) as one file. Do this weekly.
- Hostinger also keeps automatic server backups: hPanel → Files → Backups.
- Uploaded images are in `uploads/`. Include this folder when you copy the site.
- **Maintenance mode** (Settings → System) shows visitors a "back soon" page with your WhatsApp and phone, while you stay signed in.
- **Forgot your password?** Use the link on the sign-in page. It needs SMTP to be set up.

---

## 7. Security built in

- Passwords are hashed (bcrypt).
- Sessions are bound to the password, so changing it signs out other devices.
- Login attempts are throttled: 6 tries per 15 minutes per IP.
- Every form has CSRF protection.
- Public forms have a honeypot, a signed time-trap and a rate limit per visitor.
- All queries are prepared statements. Rich text is cleaned with a whitelist before it is saved.
- Uploads are validated and re-encoded, and PHP can't run inside `uploads/`.
- `app/` and `storage/` are blocked from the web. The site sends security headers.
- CSV exports are protected against spreadsheet formula injection.
- The activity log records who changed what.

---

## 8. For developers

**Run locally**
```bash
php -S localhost:8000 index.php
# open http://localhost:8000 — the installer offers SQLite for local use
```

**Structure**
```
index.php              Front controller (all requests)
.htaccess              Clean URLs, security, caching, compression
app/
  bootstrap.php        Loads libraries, config and error handling
  config.php           Written by the installer (database, app key). Keep private.
  lib/                 db, schema, settings, auth, mailer, uploads, flights, leads, html sanitiser …
  lib/resources.php    Admin content types: fields, lists and validation in one place
  lib/seed.php         Starter content loaded by the installer
  routes/              site.php (pages), api.php (JSON), admin.php (panel), install.php
  views/               layouts/, site/, admin/, partials/
  data/airports.json   Airport list for flight autocomplete
assets/
  css/site.css         Website design system (tokens at the top: colours, type scale, spacing)
  css/admin.css        Admin panel styles
  js/site.js           Website behaviour (board, flight search, forms, WhatsApp …)
  js/admin.js          Admin behaviour (media picker, editor, drag-and-drop …)
  img/                 Logo variants, icons, social share image
uploads/               Images uploaded from the admin
storage/               Sessions, logs and the SQLite database (if used)
```

**Common changes**
- To add a field to packages (or any content type), add one line to its `fields` array in `app/lib/resources.php` and a column in `app/lib/schema.php`.
- Brand colours and fonts are CSS variables at the top of `assets/css/site.css`.
- Set `'debug' => true` in `app/config.php` to see errors while developing. Never leave it on in production.

Requirements: PHP 8.0+ with PDO MySQL (or SQLite) and mbstring. GD (image resizing) and cURL (live fares) are recommended; both are on by default at Hostinger.
