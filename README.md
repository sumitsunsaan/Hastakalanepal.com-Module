# Hastakalanepal.com-Module (customersalsoviewed)
# Customers Also Viewed Module for PrestaShop

[![PrestaShop Version](https://img.shields.io/badge/PrestaShop-8.2.0%2B-brightgreen.svg)](https://www.prestashop.com)

Enhance your PrestaShop store with Amazon-style product recommendations based on customer viewing patterns.

![Module Preview](https://via.placeholder.com/800x400.png?text=Customers+Also+Viewed+Module+Preview)

## Features ✨

- **Cross-selling Engine**: Automatically suggests related products
- **Smart Tracking**: Records views for both logged-in users and guests
- **Flexible Display**:
  - Carousel or Grid layout
  - Configurable number of products (4-12 recommended)
  - Stock availability filter
- **Performance Optimized**: Lightweight SQL-based recommendations
- **Mobile Ready**: Responsive design for all devices

## Installation 📦

### Requirements
- PrestaShop 8.2.0+
- PHP 7.4+
- MySQL 5.7+

### Steps
1. Download the latest release
2. Upload to your store's `/modules/` directory
3. Install via PrestaShop Admin:
   - Go to `Modules → Module Manager`
   - Search for "Customers Also Viewed"
   - Click Install

## Configuration ⚙️

After installation, configure through:
`Modules → Module Manager → Customers Also Viewed → Configure`

| Setting | Description |
|---------|-------------|
| Products to Display | Number of recommendations (4-12) |
| Enable Carousel | Toggle sliding carousel display |
| Stock Only | Only show in-stock products |

## Usage 💡

The module automatically:
- Appears on product pages (footer section)
- Collects viewing data
- Updates recommendations daily

## For Developers 🛠️

### Hooks
- `displayHeader` - Tracking script
- `displayFooterProduct` - Recommendation display
- `actionAdminControllerSetMedia` - Admin styles

### Database Schema
```sql
CREATE TABLE `ps_product_view` (
  `id_product_view` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_product` INT(11) UNSIGNED NOT NULL,
  `id_customer` INT(11) UNSIGNED DEFAULT NULL,
  `session_id` VARCHAR(32) DEFAULT NULL,
  `date_add` DATETIME NOT NULL,
  PRIMARY KEY (`id_product_view`)
);

Thank You for Support ❤️
Community Support
Open a GitHub Issue: https://github.com/sumitsunsaan/customersalsoviewd/issues

Premium Support
Email: admin@hastakalanepal.com
Phone: +977 - 9845053769
