# 📦 Laravel E-Commerce API

## 📝 Project Description
This project is a mini E-Commerce API built with Laravel. It provides RESTful API endpoints for a website, mobile application, and admin dashboard.

The project implements the following Laravel concepts:
-  Authentication and Google OAuth
- Role-based authorization (Admin & Customer)
- API resource routing and controllers
- Localization (English & Arabic support)
- Stripe and PayPal payment integration
- Order, Cart, Product, and Category management


---

## 🛠️ Technologies Used
- PHP 8.1+
- Laravel 11
- MySQL
- Composer
- Laravel Sanctum 
- Stripe & PayPal APIs

---

## 📂 Project Features
- 🔐 **Authentication & Authorization**
  - Google OAuth login
  - Email verification before accessing protected routes
  - Admin vs. Customer role access

- 👥 **User Management**
  - Register, login, logout
  - Retrieve authenticated user information

- 🛒 **Cart System**
  - Add, update, delete products from cart
  - Clear cart functionality
  - Server-side cart using `surfsidemedia/shoppingcart`

- 🧾 **Orders**
  - Create orders from cart
  - Track order status, address, and payment method
  - Integrated with Stripe and PayPal

- 📦 **Products & Categories**
  - CRUD operations for admins
  - Relationship between products and categories
  - Paginated product listings

- 🌍 **Localization Support**
  - Supports English and Arabic
  - Language is selected via the `Accept-Language` header
  - Defaults to English

---
> ⚠️ **Important:**  
> After copying the `.env` file, make sure to update the following environment variables with your own credentials:

### 🟢 Google OAuth
```env
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT=http://127.0.0.1:8000/api/login/google/callback
STRIPE_KEY=your_stripe_key
STRIPE_SECRET=your_stripe_secret
STRIPE_WEBHOOK_SECRET=your_stripe_webhook_secret
PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_CLIENT_SECRET=your_paypal_client_secret

## 🚀 Running Locally

```bash
git clone https://github.com/mkaied10/laravel-ecommerce-api.git
cd laravel-ecommerce-api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
