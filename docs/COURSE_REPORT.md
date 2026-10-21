# Lime&Dime — A Warm Little Marketplace

A small-scale online marketplace application written in PHP / MySQL, supporting four different user roles. Developed on XAMPP.

---

## Table of Contents

1. [Vision and Motivation](#vision-and-motivation)
2. [Project Features](#project-features)
3. [Why So Many PHP Files?](#why-so-many-php-files)
4. [User Roles](#user-roles)
5. [PHP Foundation Derived from Lectures](#php-foundation-derived-from-lectures)
6. [Development Process and Decision Points](#development-process-and-decision-points)
7. [Challenges Faced and Restarts](#challenges-faced-and-restarts)
8. [AI (Claude Code) Usage — Honest Disclosure](#ai-claude-code-usage--honest-disclosure)
9. [Folder Structure](#folder-structure)
10. [Setup](#setup)

---

## Vision and Motivation

The project started from a specification our professor gave us — a "Digital Mall System" with three roles (Admin, Manager, Customer), defined database tables (`users`, `shops`, `products`, `orders`, `order_line`, `cart`), and a step-by-step task list (logo + auth → Admin Dashboard → Manager Dashboard → Customer Dashboard → DB implementation). My initial plan was to follow the spec and just deliver enough functionality to pass the course. However, our professor's habit of sharing real-life examples and personal projects truly inspired me. It shifted my perspective and motivated me to push my boundaries, turning a simple assignment into a platform that is as close to "ready-to-publish" as possible.

While the spec required three roles, I added a fourth one — **Courier** — because once I started thinking about the order flow end-to-end, it felt incomplete without someone actually delivering the order. This decision drove a chunk of the extra complexity in the project (the courier panel, online/offline status, deliveries table, accepting/picking up/delivering states). Since I had never built a web platform of this scale before, organizing the architecture was challenging, and the page count quickly exceeded my initial estimates. Ultimately, this project not only taught me practical technologies I can use in the industry but also showed me firsthand how difficult—and crucial—it is to maintain structure in a growing web application.

My ultimate vision was to create a platform with professional design, interface, and functionality that small local businesses would genuinely enjoy using. I wanted it to feel warm and "handcrafted," yet highly professional. That is where the name Lime&Dime came from: a marketplace that appreciates the small, valuable things (dime) while maintaining a fresh, natural approach (lime).

My main objectives:

- Put into practice everything covered in the lectures: Deepen my understanding of core concepts like login/signup flows, CRUD operations, session management, and MySQL relationships.
- Move beyond basic implementations: Not settle for a single user type, but successfully implement and securely manage four different roles and their respective control panels.
- Prioritize UI/UX and professionalism: Ensure the project is not just technically "functional," but also visually polished and professional-looking, recognizing that design is a critical component of a successful, real-world application.

---

## Project Features

### Customer side

- Registration and login (email + password, hashed with `password_hash`)
- Featured sellers and random products on the homepage
- Category-based product browsing (Food, Clothing, Electronics, Education, Beauty)
- Product search (`category.php?q=...`)
- Sorting (price, rating, relevance)
- Product detail page (reviews, related products, seller card)
- Seller profile page (Products / About / Reviews tabs)
- Cart and checkout (3-step: Address → Delivery type → Payment)
- Order history (filtering + detail modal + tracker)
- Order cancellation request (pending seller approval)
- Profile management (personal info, addresses, cards, favorites)
- Password change

### Seller side (`manager.php`)

- Multi-step seller registration (`manager-register.php`)
- Dashboard: today's revenue, pending order count, 30/60/90-day revenue chart (rendered with SVG)
- Product management (add / edit / delete, photo upload)
- Order tracking
- Approve/reject cancellation requests
- Delivery tracking
- Shop settings (logo, color, name, cover photo)

### Courier side (`courier.php`)

- Multi-step courier registration + document upload
- Online/offline status
- Accepting pending orders
- Active delivery tracking
- Past deliveries and earnings chart
- Today's/weekly/monthly earnings statistics

### Admin side (`admin.php`)

- Operations dashboard
- Order management
- User management (suspend, activate, delete, approve pending applications)
- Seller management (shop suspend/activate)
- Courier management (view documents)
- Product management (editing)
- Reports (CSV download: GMV, payout, tax, refunds)
- Messages (received from the contact form)
- Platform settings (commission rates, operating regions)

### General

- Footer with categories, account, selling/delivery links
- Static pages: About, Privacy, Terms, Contact

---

## Why So Many PHP Files?

At first glance, the spec looks like it could be done in 5–6 files. The project ended up with **29 PHP files** (22 top-level pages + 7 shared partials in `_partials/`). Here's the reasoning behind that count, because it's a fair question:

### 1. The spec's "Home" page is one word, but a real marketplace homepage isn't one page

The customer spec lists **Home** as a single item. But in a real marketplace, a shopper can:

- See the landing page with featured shops and random products → `index.php`
- Browse a category → `category.php`
- See all sellers in a grid → `sellers.php`
- Open a specific shop's profile → `seller.php`
- Open a specific product detail page → `product.php`

So the spec's single "Home" page became **5 separate browsing pages**, each with its own purpose.

### 2. For the dashboards, I did the opposite — multiple "pages" inside one file

The spec says "Admin Dashboard — 3 pages", "Manager Dashboard — 4 pages", "Customer Dashboard — 4 pages". I implemented each dashboard as **one PHP file with tab switching** via a `?tab=...` query string:

- `admin.php` → 9 tabs (dashboard, orders, users, sellers, products, couriers, reports, settings, messages)
- `manager.php` → 5 tabs (dashboard, products, orders, deliveries, settings)
- `courier.php` → 5 tabs (dashboard, deliveries, history, earnings, profile)

This way the role check (`require_role()`), sidebar markup, and shared data load only happens once per file. No code duplication.

### 3. Registration flows differ per role

- `register.php` → customer signup (simple, single page)
- `manager-register.php` → seller signup (4-step + document upload)
- `courier-register.php` → courier signup (4-step + document upload)

I could have crammed these into one file with role switching, but each one has different fields, different validation, and a different document upload flow. Keeping them separate is cleaner and safer.

### 4. The order pipeline deserves its own pages

- `cart.php` → cart view
- `checkout.php` → 3-step checkout (Address → Delivery → Payment)
- `order-success.php` → post-purchase confirmation
- `orders.php` → order history with detail modal and cancellation flow

Squeezing these into one file would make both the code and the URL flow messy. Each one is a distinct step in the user's journey.

### 5. Static pages add professionalism

- `about.php` / `privacy.php` / `terms.php` / `contact.php`

These are the small details that make a site feel real. `contact.php` actually writes to the database — submitted messages show up in the admin panel.

### 6. `_partials/` prevents repetition

29 files might sound like a lot, but each file is **short and focused**, and 7 of those 29 are just shared partials. The shared code in `_partials/` looks like this:

```php
include '_partials/connect.php';        // mysqli_connect
include '_partials/session.php';        // session + auth helpers
include '_partials/data.php';           // common queries loaded on every page
include '_partials/header.php';         // site header + nav
include '_partials/footer.php';         // site footer
include '_partials/icons.php';          // SVG icon functions
include '_partials/illustrations.php';  // SVG product/shop illustrations
```

So while there are 22 top-level page files, the real logic is concentrated in the 7 partials and shared across pages.

### The actual file breakdown

| Category                   | Count  | Files                                                                    |
| -------------------------- | ------ | ------------------------------------------------------------------------ |
| Auth                       | 3      | `login.php`, `register.php`, `logout.php`                                |
| Role-specific registration | 2      | `manager-register.php`, `courier-register.php`                           |
| Customer browsing          | 5      | `index.php`, `category.php`, `sellers.php`, `seller.php`, `product.php`  |
| Order pipeline             | 4      | `cart.php`, `checkout.php`, `order-success.php`, `orders.php`            |
| Customer profile           | 1      | `profile.php`                                                            |
| Role panels (multi-tab)    | 3      | `admin.php`, `manager.php`, `courier.php`                                |
| Static pages               | 4      | `about.php`, `privacy.php`, `terms.php`, `contact.php`                   |
| Shared partials            | 7      | `_partials/{connect,session,data,header,footer,icons,illustrations}.php` |
| **Total**                  | **29** |                                                                          |

---

## User Roles

| Role       | Access                 | Redirect       |
| ---------- | ---------------------- | -------------- |
| `customer` | General site + profile | `/index.php`   |
| `seller`   | + Manager panel        | `/manager.php` |
| `courier`  | + Courier panel        | `/courier.php` |
| `admin`    | Everything             | `/admin.php`   |

Roles are stored in the `users` table. Permission checks are handled by the `require_login()` and `require_role()` functions in `_partials/session.php`.

---

## PHP Foundation Derived from Lectures

I built my entire PHP infrastructure by studying the lecture examples. Each lecture was useful in the following ways:

### Lecture 1 — Basic Syntax

- `echo`, variables, reading form input
- `<?php ... ?>` blocks
- Embedding PHP within HTML

### Lecture 2 — Form Handling

- Retrieving data with `$_POST`
- Using form action and method
- Sending data with the submit button

### Lecture 3 — Functions

- Writing my own functions (`_normalize_product`, `find_seller`, `status_pill`, etc.)
- Function parameters and return values
- Sharing functions across multiple files via `include` (`_partials/illustrations.php`, `_partials/icons.php`)

### Lecture 4 — Update Operations

- Updating existing data via form
- `UPDATE` SQL usage
- This is the foundation of all my "edit" scenarios such as customer profile info, product editing, and order status updates.

### Lecture 5 — Database Queries

- Connection with `mysqli_connect`
- Fetching data with `mysqli_query`
- Reading rows with `mysqli_fetch_assoc`
- Foundation of homepage, category, product list, and similar pages.

### Lecture 6 — CRUD

- Create / Read / Update / Delete
- Index, insert, update, delete files
- Add/delete/edit product, add/delete address, add/delete card, delete user — all are copies of this structure.

### Lecture 7 — Authentication

- Password security with `password_hash` / `password_verify`
- Session management with `session_start()` and `$_SESSION`
- `mysqli_connect.php` partial file
- Login / signup / logout flow
- This became the backbone of the system. My `_partials/session.php` + `_partials/connect.php` duo is directly inspired by this lecture.

## Development Process and Decision Points

I followed the order the professor laid out in the task list. Each phase below corresponds to a specific task or tutorial we were given:

### Original Specification

The professor's brief defined the project as a "Digital Mall System" with the following schema and tasks:

```
Users:      (id, name, email, password, address, role)   [Admin, Manager, Customer]
Shops:      (id, name, type, location, manager_id, rating)
Products:   (id, name, description, price, quantity, shop_id, rating)
Orders:     (id, order_date, status, customer_id, total, tax)
Order_line: (id, product_id, order_id, amount, total_price)
Cart:       (id, order_id, order_line_id, customer_id, payment_method)
```

Shop types had to include Coffeeshop, Restaurant, Clothing, and Electronics. The tasks were sequential:

1. Logo + login page + signup page
2. Admin Dashboard (Home, Manage Users, Manage Shops)
3. Manager Dashboard (Home, Products, Orders, Deliveries)
4. Customer Dashboard (Home, Cart, Orders, Account)
5. Database implementation (create tables, add at least 1 shop with 5 products, be able to place an order and display it in cart)

I implemented in this exact order.

### Phase 1 — Logo, Login, Signup

The starting point. I designed the Lime&Dime logo (Wine + Lime + Coral palette) and built `login.php` and `register.php`. I took lecture notes as a template and extended it: added password hashing with `password_hash` / `password_verify`, role-based redirection after login, email uniqueness check on signup, and basic field validation. The `_partials/connect.php` and `_partials/session.php` partials I rely on across the project were also written in this phase.

### Phase 2 — Admin Dashboard

Per the spec, this had three pages: Home (analytics + charts), Manage Users (table with edit/delete), Manage Shops (table with edit/delete), plus a navigation bar (Home / Manage Users / Manage Shops / Log out). I expanded this further into a sidebar layout with extra sections (Orders, Products, Couriers, Reports, Settings, Messages) so the admin could oversee everything that was about to come. The home page got revenue, today's orders, active sellers, and pending applications stats. Reports gained CSV export for GMV, payouts, courier earnings, taxes, and refunds.

### Phase 3 — Manager Dashboard

Per the spec, four pages: Home (stats + optional charts), Products (card format with update/delete + "Add product" / "Add category" buttons), Orders (table with accept/reject), Deliveries (accepted orders with cancel/confirm), plus a sidebar (Home / Products / Orders / Deliveries / Log out). I followed this structure. I added:

- A 30/60/90-day revenue chart (rendered with SVG)
- A cancellation request approval section in Orders (so the customer can request a cancel and the manager approves or denies it)
- A shop settings page (logo color, shop avatar, cover image upload)
- Multi-step seller registration (`manager-register.php`) with document upload (Gov ID, business license, tax form, bank account, it doesn't need to be added to register; the admin can approve or reject it.)

### Phase 4 — Customer Dashboard

Per the spec: Home (shops as cards with "View Store" button → store page with products + "Add to Cart"), Cart (item details, amounts, total + "Confirm Order"), Orders (table with status pills, cancel button only when status is Pending or Accepted), Account (email, name, address). Navigation bar shared across all pages (Home / Cart / Orders / Account / Log Out). I followed this structure but expanded each piece:

- Home → I split into `index.php` (featured + random products), `category.php` (category browsing + search + sort), `sellers.php` (all shops)
- Product detail page (`product.php`) with reviews and related products
- Seller detail page (`seller.php`) with Products / About / Reviews tabs
- Cart → kept the spec's cart logic but moved it to localStorage for instant updates
- Checkout split into 3 steps (Address → Delivery type → Payment) for clarity
- Orders → added order tracker, cancel-request modal, and detail modal
- Account → expanded into profile / addresses / cards / favorites tabs with their own modals

### Phase 5 — Database Implementation

This was when I actually built the database schema in MySQL. Created `users`, `shops`, `products`, `orders`, `order_line`, plus pictures-related columns. Added at least one shop with five products, verified that I could place an order and display it in the cart. This is also when I switched the cart from localStorage to actually creating a real `orders` row in the DB at checkout.

### Phase 6 — Courier role (beyond spec)

The spec didn't require a courier, but the order flow felt incomplete without delivery. I added a `Courier` role, a `deliveries` table, a courier panel (`courier.php`) with online/offline toggle, available-orders queue, active-deliveries table, history, and earnings stats. The courier registration (`courier-register.php`) was multi-step with real document upload (Gov ID, license, insurance, tax form).

### Phase 7 — Design polish

Once the core functionality was working, I went back and polished the visual layer. The professor allowed using AI for the HTML/CSS, so I had AI generate a custom design system (`assets/css/styles.css` — Wine + Lime + Coral palette, Fraunces font, card-based layout). I iterated on this several times until it matched the warm "small shop" feel I wanted. SVG illustrations in `_partials/illustrations.php` and SVG icons in `_partials/icons.php` were also generated and refined together with AI.

### Phase 8 — Final integration pass

Trackers (order tracking), pills (status labels), revenue charts on the manager dashboard, multi-step form animations — all were tightened up. I also did a final consistency pass over the entire codebase.

**Phase 5 — Multi-step registration forms**
I didn't want to put the seller and courier registration flows on a single page — there's too much information. So I wrote 4-step forms that show progress step by step with JS.

**Phase 6 — Document upload**
Real document upload was added to courier and seller applications (gov ID, license, tax form, bank). The `documents` table and `assets/docs/{user_id}/` folder structure.

**Phase 7 — Design improvements**
In the early versions, the site looked like a "student assignment". The Wine + Lime + Coral color palette, Fraunces font, and card-based layout — these were generated and iterated on with AI assistance (which the professor allowed for HTML/CSS). I went back after the core functionality was done and polished the entire visual layer.

**Phase 8 — Final polish**
Trackers (order tracking), pills (status labels), SVG illustrations (8 different product icons in `_partials/illustrations.php`), and charts (manager dashboard revenue chart with SVG) — all visual elements were refined together with AI in this phase.

---

## Challenges Faced and Restarts

While building this project, I **restarted from scratch several times**. Honestly, I did at least 3 major rewrites:

**1. The initial DB design didn't survive contact with reality**
I started by building exactly what the spec described (`users`, `shops`, `products`, `orders`, `order_line`, `cart`). But as I implemented the panels, I realized I was missing things — couriers needed a `deliveries` table, customers needed multiple `addresses` and `payment_cards`, document uploads needed a `documents` table, the contact form needed a `contact_messages` table, and platform-wide settings needed `platform_settings`. I expanded the schema in pieces, but the relationships between old and new tables broke each other along the way. DROP'd the DB and rebuilt it from scratch twice during this growth.

**2. Foreign key relationships**
In the first version, relationships like `orders.customer_id`, `order_line.product_id` had no foreign keys — they were just INTs. JOINs were working but "orphan" rows formed. At one point I deleted a user, their orders stayed, and the panel broke. I completely lost control — threw out the old DB and rebuilt from scratch.

**3. Order statuses got tangled**
The spec listed `pending, accepted, delivered, canceled` as the basic statuses, but once couriers entered the picture I needed `packed`, `out_for_delivery`, and `cancel_requested` (for the manager-approved cancel flow) as well. Establishing the right transition flow between all seven states took time. At one point, when the courier panel accepted an order, the status was jumping directly to `out_for_delivery` instead of `accepted`. I had to empty the order table and reset the test data again.

**4. Session and role management**
The spec had three roles (Admin, Manager, Customer); I added Courier as a fourth. When I added the courier role, I had to retrofit the session structure that had been built around three roles. Wrote the `require_role()` function in `_partials/session.php` to handle all four. During this transition, the old `is_admin=1` style flag I had been using in early test code conflicted with the new `role` column lookups, and I ended up in an infinite redirect loop between login and the panels for an entire day.

**5. The design went through two rewrites**
My first version used a quick rough layout — it gave off a "school assignment" vibe. Since the professor allowed using AI for HTML/CSS, I had AI generate a proper custom design system (`assets/css/styles.css` — custom CSS with variables, Wine + Lime + Coral palette, Fraunces font). I iterated on it a few times until it looked the way I wanted. That polishing process took about a week.

---

## AI (Claude Code) Usage — Honest Disclosure

> I want to be transparent with the professor about where AI was involved in this project. The professor explicitly allowed AI for the HTML/CSS side, so the visual layer was AI-assisted from the start. For the PHP/MySQL side, I wrote the foundation myself based on the lectures and only used AI when the project got too large to debug and integrate alone.

### What I Wrote Myself

The PHP and database foundation is mine. Specifically:

- **DB design** — Decided which tables would exist (`users`, `shops`, `products`, `orders`, `order_line`, `deliveries`, etc.), which columns each would have, and how they would be related. This was a long process of trial and error (#challenges-faced-and-restarts.
- **SQL queries** — Wrote the `mysqli_query` calls in each page based on what I learned in Lecture 5–7. The query patterns (SELECT with JOIN, INSERT, UPDATE) are direct extensions of the lecture examples.
- **Authentication flow** — Login, registration, session, role-based redirection. I took lecture notes as a template and extended it.
- **File layout / how pages connect** — Which page does what, which file includes which partial, when `require_role()` is called — these are design decisions I made.

I did this by studying the professor's lecture examples and applying the same patterns. The structures `mysqli_connect → mysqli_query → mysqli_fetch_assoc`, `password_hash` + `password_verify`, `$_SESSION['user']` — all of these are direct applications of what we covered in class.

### Where AI Helped

**1. HTML / CSS / Design (allowed by the professor)**
The entire visual layer — `assets/css/styles.css`, layouts in the `.php` files (card structures, modals, form designs), SVG illustrations in `_partials/illustrations.php`, SVG icons in `_partials/icons.php` — was generated and iterated on with AI. I gave directions like "I want a warm marketplace look", "use this color palette", "make the seller card like this", and AI produced and refined the HTML/CSS.

**2. Debugging when the project got too big**
When the project reached around 5000 lines and 19 files, I had a hard time tracking everything by myself. AI helped me catch bugs that I couldn't easily find:

- The "Submit application" button wasn't working because there was an `<input name="submit">` inside the form which was overriding the form's JS `submit()` method. I would have spent hours finding this; AI diagnosed it in seconds.
- A shop I suspended was still appearing — turned out the `status` column was an ENUM that didn't accept the `'suspended'` value. AI tracked this down to the DB schema.
- When I added new features, sometimes I would use a DB column that didn't exist yet. AI scanned the code and pulled out all missing columns as a pending SQL list.

**3. Cross-file consistency**
Since the project has 19 files, when I made a change in one file, I sometimes forgot to update related places elsewhere. For example, I changed the `step_map` in `data.php` but forgot to make the tracker in `orders.php` dynamic — AI caught this.

**5. Translation and comment cleanup**
I had Turkish comments and a few Turkish UI labels so I wouldn't get confused while developing. For the final version, I translated everything to English and removed unnecessary comments — AI helped me batch this.

### Summary

- The **HTML/CSS design layer** — AI-assisted
- The **PHP/MySQL foundation, queries, auth flow** — main logic written by me, based on the lectures
- **Debugging and consistency checks** — AI was a tool I used when the project got too large

## Folder Structure

```
limedime/
├── _partials/              # Shared parts (header, footer, session, db conn)
│   ├── connect.php         # mysqli_connect
│   ├── session.php         # session, require_login, require_role
│   ├── data.php            # Common data loaded at the start of pages
│   ├── header.php          # Site header
│   ├── footer.php          # Site footer
│   ├── icons.php           # SVG icon functions
│   └── illustrations.php   # SVG product/shop illustrations
│
├── assets/
│   ├── css/styles.css      # Entire design system (custom CSS)
│   ├── js/app.js           # General JS (favorites, cart counter)
│   ├── img/                # Logo, product/shop photos
│   └── docs/               # Uploaded application documents
│
│
├── index.php               # Homepage
├── category.php            # Category + search
├── product.php             # Product detail
├── sellers.php             # All sellers
├── seller.php              # Seller detail
├── cart.php                # Cart (localStorage-based)
├── checkout.php            # 3-step order completion
├── order-success.php       # Order confirmation page
├── orders.php              # Order history
├── profile.php             # Customer profile + favorites + address + card
│
├── login.php               # Sign in
├── register.php            # Customer registration
├── manager-register.php    # Seller registration (4-step)
├── courier-register.php    # Courier registration (4-step)
├── logout.php              # Sign out
│
├── manager.php             # Seller panel
├── courier.php             # Courier panel
├── admin.php               # Admin panel
│
├── about.php               # About us
├── privacy.php             # Privacy
├── terms.php               # Terms of service
├── contact.php             # Contact
│
└── README.md               # This file
```

---

## Setup

### Requirements

- XAMPP (Apache + MySQL + PHP 7.4+)
- Place the project in the `htdocs/limedime/` folder

### Database Setup

1. Open phpMyAdmin.
2. Create a new database: `lime_dime_db`
3. There is an SQL dump containing the following tables (will be provided with the project submission).

### Configuration

`_partials/connect.php` file:

```php
$conn = mysqli_connect("localhost", "root", "", "lime_dime_db");
```

### Test Users

```
Admin:    admin@limedime.com    / admin123
Seller:   seller@limedime.com   / seller123
Courier:  courier@limedime.com  / courier123
Customer: customer@limedime.com / password
```

### Running

Go to `http://localhost/limedime/index.php`.

---

## License and Contribution

This is a course project. It is designed for ITEC327 class.

Developer: Umut Tuna
Course: Server-Side Internet and Web Programming (ITEC327)
