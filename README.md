# Temporary (Spam) Email Checker

A PHP service that validates email domains against whitelist and blacklist, with automatic disposable domain detection using our known database and UserCheck API.

## Features

- ✅ **Whitelist Checking** - Quick validation against legitimate domains
- ✅ **Blacklist Checking** - Prevents known disposable/spam domains
- ✅ **API Integration** - Automatic disposable domain detection via UserCheck API
- ✅ **Smart Caching** - Automatically maintained whitelist and blacklist
- ✅ **File-Based Storage** - No database required, simple file management
- ✅ **Indexed Cache** - Ultra-fast lookups using indexed cache files by domain prefix

## Cache System

The system uses **indexed cache files** for ultra-fast domain lookups:

### How It Works

- **Master files** (`data/white.list`, `data/black.list`) contain your source data and remain unchanged
- **Cache files** (`.cache/whitelist-*.list`, `.cache/blacklist-*.list`) are used for runtime lookups
- **Indexing by prefix** - Domains are stored in separate files based on their first character:
  - `whitelist-a.list` → domains starting with 'a'
  - `whitelist-b.list` → domains starting with 'b'
  - ... continue for all 26 letters
  - `whitelist-others.list` → domains starting with numbers 0-9

### Benefits

- ✅ **Faster lookups** - Search only in relevant cache file
- ✅ **Original data safe** - Master files never modified
- ✅ **Easy management** - New domains appended only to cache
- ✅ **Minimal memory** - Load only relevant portions

### Initial Setup

Before first use, build the cache from your master data files:

```bash
php configure.php
```

This command:
1. Reads `data/white.list` and `data/black.list`
2. Creates indexed cache files in `.cache/` folder
3. Removes duplicates and sorts domains
4. Displays statistics

**Sample output:**
```
========================================
Building Cache Configuration
========================================

Building WHITELIST cache from /var/www/data/white.list...
Created cache: /var/www/.cache/whitelist-g.list (50 domains)
Created cache: /var/www/.cache/whitelist-y.list (75 domains)
Total domains in whitelist: 5000

Building BLACKLIST cache from /var/www/data/black.list...
Created cache: /var/www/.cache/blacklist-0.list (200 domains)
Created cache: /var/www/.cache/blacklist-d.list (450 domains)
Total domains in blacklist: 50000

========================================
✓ Cache configuration completed successfully!
```

### Verify Cache

Check cache integrity anytime:

```bash
# Command line
php configure.php verify
```

### Rebuild Cache

When you manually update `data/white.list` or `data/black.list`, rebuild the cache:

```bash
php configure.php
```

### How New Domains Are Stored

When a new domain is validated via the API:
- It's **appended to the cache file** (not the master file)
- Organized by its first character automatically
- Master `data/white.list` and `data/black.list` remain unchanged
- Next `configure.php` run will consolidate cache back to master files if needed

- PHP 7.4+
- file_get_contents with stream context support
- Write permissions for list files

## Installation

1. Clone or download the files to your web directory:
```bash
cd /var/www/html/services-my/spam-email-checker
```

2. Set permission to .cache directory:
```bash
chmod 755 .cache
```

3. Add your master domain lists to `data/`:
```bash
# Create or add your domains
echo "gmail.com" >> data/white.list
echo "yahoo.com" >> data/white.list

echo "0-mail.com" >> data/black.list
```

4. Build the cache (IMPORTANT!):
```bash
php configure.php
```

5. Set proper permissions:
```bash
chmod 644 index.php configure.php README.md
chmod 755 data .cache
chmod 644 data/*.list .cache/*.list
```

6. Update the API key in `index.php`:
```php
$userCheckApiKey = 'your-usercheck-api-key-here';
```

## Configuration

### API Key
Get your UserCheck API key from [usercheck.com](https://usercheck.com) and add it to `index.php`:

```php
$userCheckApiKey = 'prd_YourApiKeyHere';
```

### Cache Directory
Cache files are stored in `.cache/` folder. Ensure it's writable:
```bash
chmod 755 .cache
```

### Master Data Files
Keep your master lists in `data/` folder:
```bash
data/white.list  # Original whitelist (unchanged by the service)
data/black.list  # Original blacklist (unchanged by the service)
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
2. **Check Whitelist Cache** - Returns `true` if found in `whitelist-*.list` cache
3. **Check Blacklist Cache** - Returns `false` if found in `blacklist-*.list` cache
4. **API Check** - Calls UserCheck API to detect disposable domains:
   - If disposable → **Appends to** `blacklist-*.list` cache → Returns `false`
   - If legitimate → **Appends to** `whitelist-*.list` cache → Returns `true`

**Important:** Master files (`data/white.list`, `data/black.list`) are never modified. New domains are only added to cache files.

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