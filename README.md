# 🎯 Oasis Spin Wheel

A sophisticated spinning wheel application with probability-based mechanics, weekly variations, and comprehensive admin management system. Perfect for promotional campaigns and discount distribution.

## ✨ Features

### 🎲 Spin Wheel System
- **Probability-based mechanics** with weekly variations
- **One spin per user** based on unique recorded ID
- **Mobile-optimized** responsive design
- **Real-time animations** with confetti effects
- **Secure API** with rate limiting and validation

### 🎁 Discount Management
- **6 default discount tiers**: 10K, 15K, 20K, 50K, 1 Lakh, Free IVF
- **Weekly probability variations** (4-week cycle)
- **Unique coupon codes** for each discount
- **Customizable colors** and display text

### 📊 Admin Panel
- **Dashboard** with real-time metrics
- **Coupon management** (add, edit, delete)
- **Spin history** tracking
- **Weekly probability** configuration
- **User activity** monitoring
- **Settings management**

### 🔒 Security Features
- **Rate limiting** (max 10 spins per IP per hour)
- **Input validation** and sanitization
- **Session management** for admin access
- **Password hashing** with PHP's password_hash()
- **SQL injection** protection with prepared statements

## 📁 Project Structure

```
Oasispin/
├── index.php              # Main spin wheel interface
├── admin.php              # Admin panel
├── login.php              # Admin login page
├── setup.php              # Setup verification page
├── README.md              # This file
└── api/
    ├── config.php          # Database configuration & helper functions
    ├── spin.php            # Spin wheel API endpoint
    ├── get-wheel-data.php  # Wheel data API
    ├── admin-data.php      # Admin panel API
    └── login.php           # Login API endpoint
```

## 🚀 Quick Start

### 1. Setup Database
1. Create a MySQL database named `oasispin`
2. Update database credentials in `api/config.php`:
   ```php
   $host = 'localhost';
   $dbname = 'oasispin';
   $username = 'your_username';
   $password = 'your_password';
   ```

### 2. Installation
1. Upload all files to your web server
2. Navigate to `setup.php` to verify installation
3. Tables and default data will be created automatically

### 3. Default Credentials
- **Username**: `admin`
- **Password**: `admin123`

⚠️ **Important**: Change these credentials in production!

### 4. Usage
- **Spin Wheel**: `index.php?recordedId=USER_ID`
- **Admin Panel**: Access via `admin.php`

## 🎯 How index.php Works

### Core Application Architecture

The `index.php` file serves as the main interface for the Oasis Spin Wheel application. Here's how it operates:

#### 1. User Authentication & Session Management
- **Phone Number Login**: Users must enter a 10-digit phone number to participate
- **Session Storage**: Phone numbers are stored in browser `sessionStorage` as `recordedId`
- **URL Parameter Support**: Can accept `recordedId` via URL parameter for direct access
- **One-Time Participation**: Each phone number can only spin the wheel once

#### 2. Dynamic Wheel Configuration
- **API Integration**: Loads wheel data from `api/get-wheel-data.php` using the user's phone number
- **Probability-Based Segments**: Each segment size is calculated based on configurable probabilities
- **SVG Rendering**: Creates wheel segments using scalable vector graphics for crisp display
- **Fallback System**: Uses hardcoded segments when API is unavailable for testing

#### 3. Sophisticated Spinning Mechanism
- **Physics Simulation**: 4-second rotation with cubic-bezier easing for realistic feel
- **Probability-Based Landing**: Calculates precise rotation to land on API-selected winning segment
- **Mathematical Precision**: Accounts for segment gaps and probability weights
- **Visual Feedback**: Real-time rotation tracking with comprehensive logging

#### 4. Result Management & Display
- **API Result Selection**: Winning segment determined by `api/spin.php` endpoint
- **Coupon Code Generation**: Each win generates a unique coupon code
- **Result Persistence**: Previous results displayed to prevent multiple spins
- **Celebration Effects**: Confetti animation and visual feedback on win

### User Journey
1. User receives SMS with link: `yoursite.com/index.php?recordedId=123`
2. User clicks link and sees the spin wheel
3. User taps "SPIN" button (one chance only)
4. System determines winning discount based on current week probabilities
5. User sees result with discount and coupon code
6. Subsequent visits show previous result (no re-spinning)

### Technical Implementation Details

#### Frontend Components
1. **Responsive Design**: Mobile-first approach with breakpoints at 640px and 768px
2. **Glass Morphism UI**: Modern design with backdrop blur effects and gradients
3. **Animation System**: CSS transitions combined with JavaScript rotation calculations
4. **Modal System**: Phone login and privacy policy modals with proper state management
5. **Touch Optimization**: Prevents zoom on double-tap, optimized for mobile interaction

#### JavaScript Architecture
- **State Management**: Tracks spinning state, user eligibility, and current wheel rotation
- **API Communication**: Handles async requests with proper error handling and fallbacks
- **Mathematical Calculations**: Complex rotation logic considering probability weights and segment positioning
- **Event Handling**: Form validation, modal management, and user interaction responses

#### Security & Validation
- **Phone Number Validation**: Ensures exactly 10-digit format with regex validation
- **Input Sanitization**: Cleans and validates all user inputs
- **Spin Prevention**: Multiple layers to prevent duplicate spins per user
- **Error Handling**: Graceful degradation when APIs are unavailable

### Probability System
The system uses a 4-week rotating probability cycle:

| Discount | Value | Week 1 | Week 2 | Week 3 | Week 4 |
|----------|-------|--------|--------|--------|--------|
| 10K Discount | 10001 | 65% | 65% | 65% | 65% |
| 15K Discount | 15001 | 22% | 23% | 22% | 23% |
| 20K Discount | 20001 | 5% | 5% | 5% | 5% |
| 50K Discount | 50001 | 3% | 3% | 3% | 3% |
| 1 Lakh Discount | 100001 | 2% | 2% | 2% | 2% |
| Free IVF | 200001 | 3% | 2% | 3% | 2% |

### Database Schema

#### Key Tables
- **`coupons`**: Stores discount information and weekly probabilities
- **`spins`**: Records all spin results
- **`user_spins`**: Tracks user spin counts (enforces one-spin limit)
- **`weekly_settings`**: Manages current week and auto-rotation
- **`admin_users`**: Admin authentication
- **`settings`**: Application configuration

## 🔧 Configuration

### Weekly Probability Management
Admins can:
- View current week number
- Manually change the current week
- Modify probability percentages for each week
- Add new discount tiers with custom probabilities

### Coupon Management
- Add new discount offers
- Set different probabilities for each week
- Customize display colors
- Generate unique coupon codes
- Enable/disable specific offers

### Security Settings
- Rate limiting configuration
- Input validation rules
- Session timeout settings
- Password policies

## 📊 Admin Features

### Dashboard Metrics
- Total spins count
- Active coupons count
- Unique users count
- Recent activity feed
- Weekly statistics with win rates

### Spin History
- Complete audit trail
- User ID tracking
- IP address logging
- Timestamp recording
- Result tracking

### User Management
- View all user spins
- Check spin limits
- Monitor suspicious activity
- Export data capabilities

## 🛡️ Security Measures

### Input Validation
- RecordedId format validation (alphanumeric, max 50 chars)
- SQL injection prevention with prepared statements
- XSS protection with output escaping

### Rate Limiting
- Maximum 10 spins per IP address per hour
- Configurable throttling limits
- Automatic IP blocking for abuse

### Authentication
- Secure password hashing
- Session management
- Admin-only API endpoints
- CSRF protection

### Data Protection
- Secure database connections
- Error logging without exposing sensitive data
- Input sanitization at all entry points

## 🔧 API Endpoints

### Public APIs
- `GET api/get-wheel-data.php?recordedId=ID` - Get wheel configuration
- `POST api/spin.php` - Execute spin (requires recordedId)

### Admin APIs (Authentication Required)
- `GET api/admin-data.php?action=dashboard` - Dashboard data
- `GET api/admin-data.php?action=coupons` - Coupon list
- `GET api/admin-data.php?action=spins` - Spin history
- `POST api/admin-data.php` - Add coupon/Update week
- `PUT api/admin-data.php` - Update coupon/Settings
- `DELETE api/admin-data.php` - Delete coupon

## 📱 Mobile Optimization

- Responsive design for all screen sizes
- Touch-optimized spin button
- Smooth animations and transitions
- Prevent zoom on double-tap
- Optimized loading for mobile networks

## 🎨 Customization

### Visual Customization
- Modify colors in CSS variables
- Update wheel segment colors via admin panel
- Customize animations and effects
- Brand with your own logo and styling

### Functional Customization
- Adjust probability percentages
- Modify rate limiting rules
- Customize error messages
- Add new discount tiers

## 🚀 Production Deployment

### Security Checklist
- [ ] Change default admin credentials
- [ ] Update database credentials
- [ ] Enable HTTPS
- [ ] Configure proper file permissions
- [ ] Set up regular backups
- [ ] Configure error logging
- [ ] Review rate limiting settings

### Performance Optimization
- [ ] Enable PHP OPcache
- [ ] Configure database indexes
- [ ] Set up CDN for static assets
- [ ] Enable GZIP compression
- [ ] Monitor database performance

## 🐛 Troubleshooting

### Common Issues

#### Database Connection Failed
- Check database credentials in `api/config.php`
- Verify MySQL service is running
- Ensure database exists and is accessible

#### Spin Not Working
- Check browser console for JavaScript errors
- Verify recordedId parameter in URL
- Check if user has already spun (limit: 1 per user)

#### Admin Panel Access Denied
- Verify login credentials
- Check PHP session configuration
- Clear browser cookies and try again

### Error Logs
Check your web server error logs for detailed error messages. Common log locations:
- Apache: `/var/log/apache2/error.log`
- Nginx: `/var/log/nginx/error.log`
- cPanel: Access logs via File Manager or cPanel interface

## 📞 Support

For technical support or questions:
1. Check this README file
2. Review error logs
3. Test with `setup.php` verification page
4. Check browser console for JavaScript errors

## 📄 License

This project is proprietary software developed for Oasis India. All rights reserved.

---

**🎯 Happy Spinning! 🎯**