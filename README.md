# Veriteworks Paypal Payments

Module Veriteworks\Paypal implements integration with the Paypal payment system.

## How To Install
You can install this module from packagist.
```bash
composer require --dev veriteworks/paypal
```

you can also install this module by cloning this GitHub repository.
```bash
git clone git@github.com:veriteworks/paypal.git
```

## Option
You can set the options from the Admin of your site.

1. Go to STORES / SALES / Payment Methods / Veriteworks Paypal
2. Set "Enabled" to "Yes"
3. Set the Merchant ID, the Merchant Password, the Auth Type, the Use 3D Secure and the Access Token (is mentioned below.).

### Access Token
A new Access Token is retrieved every 5 hours.
When installing this module for the first time, you can run the cron as below.
```bash
bin/magento cron:run --group veriteworks_paypal
```
