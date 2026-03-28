# Hotel Booking Search API

A Laravel-based hotel booking search API that provides room availability and pricing with dynamic discounts.

## Features

- Search for available rooms by date range
- Support for two room types (Standard and Deluxe)
- Dynamic pricing with seasonal variations
- Two discount types:
  - Long Stay Discount (10-18% off for stays 3+ nights)
  - Last Minute Discount (20-25% off for bookings within 3 days)
- Meal plan options (Room Only, Breakfast Included)
- Real-time availability checking
- Price calculation with applied discounts
- PostgreSQL database support

## Requirements

- PHP >= 8.1
- Composer
- PostgreSQL >= 10.0
- Laravel 10.x

## Installation

1. Clone the repository
```bash
git clone https://github.com/priyanktank6/hotel-booking-search.git
cd hotel-booking-search
