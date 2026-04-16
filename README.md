# Spam Email Checker

A PHP service that validates email domains against whitelist and blacklist, with automatic disposable domain detection using our known database and UserCheck API.

## Features

- ✅ **Whitelist Checking** - Quick validation against legitimate domains
- ✅ **Blacklist Checking** - Prevents known disposable/spam domains
- ✅ **API Integration** - Automatic disposable domain detection via UserCheck API
- ✅ **Smart Caching** - Automatically maintained whitelist and blacklist
- ✅ **File-Based Storage** - No database required, simple file management


## Requirements

- PHP 7.4+
- file_get_contents with stream context support
- Write permissions for list files

## Installation

1. Clone or download the files to your web directory:
```bash
cd /var/www/html/services-my/spam-email-checker
```

2. Create the data directory if using it:
```bash
mkdir -p data
chmod 755 data
```

3. Set proper permissions:
```bash
chmod 644 index.php white.list black.list
chmod 755 .
```

4. Update the API key in `index.php`:
```php
$userCheckApiKey = 'your-usercheck-api-key-here';
```

## Configuration

### API Key
Get your UserCheck API key from [usercheck.com](https://usercheck.com) and add it to `index.php`:

```php
$userCheckApiKey = 'prd_YourApiKeyHere';
```

## API Usage

### Check Email Domain

**Request:**
```bash
curl -X POST https://my-spam-filter.local/index.php \
  -d "email=user@example.com"
```

## Workflow

The service follows this logic:

1. **Extract Domain** - Gets domain from email address
2. **Check Whitelist** - Returns `true` if domain is whitelisted
3. **Check Blacklist** - Returns `false` if domain is blacklisted
4. **API Check** - Calls UserCheck API to detect disposable domains:
   - If disposable → Adds to blacklist → Returns `false`
   - If legitimate → Adds to whitelist → Returns `true`

## List File Format

Both `white.list` and `black.list` are plain text files with one domain per line:

```
gmail.com
yahoo.com
outlook.com
company.com
```

### Initial Whitelist
```
gmail.com
yahoo.com
ymail.com
prothomalo.com
```

### Blacklist Examples
```
0-30-24.com
0-mail.com
00.pe
00082cc.com
```

## Error Handling

The service throws exceptions for:
- Invalid email format
- API call failures
- Blacklisted domains

Handle exceptions in your integration:
```php
try {
    $isValid = checkEmailDomain($email);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## UserCheck API

The service integrates with [UserCheck](https://usercheck.com) API to verify disposable domains.

**API Endpoint:** `https://api.usercheck.com/domain/{domain}`

**Headers:** `Authorization: Bearer {api_key}`

**Response:**
```json
{
  "disposable": false,
  "domain": "example.com"
}
```

## License

MIT License - Feel free to use and modify