# Sibahle We Are Beautiful | E-Commerce Platform

A fully functional, custom-built e-commerce web application designed for **Sibahle**, a proudly South African lifestyle brand. This platform transitions the brand from informal social media sales to a professional digital storefront, showcasing African-inspired apparel, handcrafted herbal teas, bath salts, and wellness products.

## 🌍 Live Demo
**View the live website here:** [https://sibahle.great-site.net](https://sibahle.great-site.net)

*(Note: The site is hosted on InfinityFree. If the database connection times out upon first load, please wait a few seconds and refresh.)*

---

## 🎨 Design Philosophy
The UI/UX design is heavily inspired by African heritage, utilizing earthy tones (deep chocolate, terracotta, and mustard gold) and classic typography to reflect the brand's philosophy: *"Sibahle Ngobuntu Bethu" (Beautiful Through Our Humanity)*. It is completely mobile-responsive, ensuring a seamless shopping experience across all devices.

---

## ✨ Key Features

### 🛒 Customer Storefront
*   **Dynamic Product Catalog:** Browse products by categories (Clothes, Herbal Products, Bath Salts, etc.).
*   **Variant System:** Select sizes or flavors. Out-of-stock variants are automatically marked as `[SOLD OUT]` and the "Add to Cart" button is disabled.
*   **Multi-Image Galleries:** View multiple high-quality images per product with interactive thumbnails.
*   **Persistent Cart:** Uses PHP Sessions for guests and securely migrates to MySQL for logged-in users.
*   **Secure Checkout:** Complete order processing with address validation and a checkout flow.
*   **User Accounts:** Customers can register, log in securely, and view their personal order history.

### 🛡️ Admin Dashboard
*   **Role-Based Access Control:** Separate views and permissions for Admins vs. Customers.
*   **Analytics Overview:** View total sales, total orders, and total active products.
*   **Inventory Management:** Add, edit, or deactivate products. Upload a main image and up to 4 gallery images per product.
*   **Variant Management:** Update stock levels and price adjustments for specific sizes/flavors on the fly.
*   **Order Management:** View customer orders, inspect order items via modal, and update order statuses (New ➔ Processing ➔ Shipped ➔ Delivered).
*   **Customer Management:** View registered users, promote customers to Admins, or delete accounts.

---

## 💻 Technology Stack
*   **Frontend:** HTML5, CSS3, JavaScript (Vanilla/AJAX), Bootstrap 5, FontAwesome.
*   **Backend:** PHP 8 (Object-Oriented, PDO).
*   **Database:** MySQL (Relational Database Management System).
*   **Server:** Apache (Ubuntu LAMP Stack for local dev / InfinityFree for production).
*   **Security:** PDO Prepared Statements (SQLi prevention), `htmlspecialchars()` (XSS prevention), Password Hashing (`password_hash`), CSRF Tokens for form submissions.

---

## 🚀 Local Installation Guide

If you want to run this project locally on your machine (using XAMPP, WAMP, or a LAMP stack):

1. **Clone the repository:**
   ```bash
   git clone https://github.com/Kim-Haruno/sibahle-website.git
