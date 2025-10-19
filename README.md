# Aurora Magento 2 Project

A Magento 2.4.8 development environment using [Mark Shust's Docker configuration](https://github.com/markshust/docker-magento) with the default Luma theme.

## 🚀 Quick Start

### Prerequisites

- **Docker Desktop** (Mac/Windows) or **Docker Engine** (Linux)
- **Git** for version control
- **Composer** (optional, for local development)

### Initial Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd aurora-project
   ```

2. **Create environment files**
   ```bash
   # Copy environment templates
   cp env/db.env.example env/db.env
   cp env/magento.env.example env/magento.env
   cp env/phpfpm.env.example env/phpfpm.env
   cp env/redis.env.example env/redis.env
   cp env/rabbitmq.env.example env/rabbitmq.env
   cp env/opensearch.env.example env/opensearch.env
   
   # Edit the files with your own values "Specify passwords or the script will throw errors."
   # Especially change passwords in db.env, magento.env, and rabbitmq.env
   ```

3. **Configure Composer authentication for Magento Marketplace**
   ```bash
   # Set up composer authentication with your Magento Marketplace credentials
   bin/composer config --global http-basic.repo.magento.com <public_key> <private_key>
   ```
   > **Note**: Replace `<public_key>` and `<private_key>` with your actual Magento Marketplace credentials. You can find these in your [Magento Marketplace account](https://marketplace.magento.com/customer/accessKeys/).

4. **Start the Docker environment (without development tools)**
   ```bash
   docker compose up --build --force-recreate -d
   ```

5. **Copy files to container and install dependencies**
   ```bash
   bin/copytocontainer --all
   bin/composer install
   ```

6. **Install Magento with main domain**
   ```bash
   bin/setup-install aurora.local
   bin/magento setup:upgrade
   bin/magento setup:static-content:deploy -f
   bin/magento setup:di:compile
   ```

7. **Add domains to /etc/hosts**
   ```bash
   # Add these lines to your /etc/hosts file:
   sudo nano /etc/hosts
   
   # Add the following entries:
   127.0.0.1 aurora.local
   127.0.0.1 aurora.fr.local
   127.0.0.1 aurora.ie.local
   127.0.0.1 aurora.it.local
   ```

8. **Setup SSL certificates for all domains**
   ```bash
   bin/setup-ssl aurora.local
   bin/setup-ssl aurora.fr.local
   bin/setup-ssl aurora.ie.local
   bin/setup-ssl aurora.it.local
   ```

9. **Restart containers to apply all changes**
   ```bash
   bin/restart
   ```

10. **Access your sites**
   - **Main Store (English)**: `https://aurora.local`
   - **French Store**: `https://aurora.fr.local`
   - **Irish Store**: `https://aurora.ie.local`
   - **Italian Store**: `https://aurora.it.local`
   - **Admin Panel**: `https://aurora.local/admin`
   - **Admin credentials**: As configured in `env/magento.env`

### **Common Issues**
1. Tried entering one of the domains but it threw 403 Forbidden issue. Here is the fix:
   ```bash
   docker exec aurora-project-app-1 cp /var/www/html/nginx.conf.sample /var/www/html/nginx.conf
   docker restart aurora-project-app-1
   ```
   aurora-project-app-1 replace this with your nginx container name


## 📋 Complete Setup Checklist for New Developers

- [ ] Docker Desktop installed and running
- [ ] Git repository cloned
- [ ] Environment files created from examples
- [ ] Environment files edited with secure passwords
- [ ] Composer authentication configured for Magento Marketplace
- [ ] Containers started (`bin/start --no-dev`)
- [ ] Files copied to container (`bin/copytocontainer --all`)
- [ ] Composer dependencies installed (`bin/composer install`)
- [ ] Magento setup completed (`bin/setup-install aurora.local`)
- [ ] Domains added to `/etc/hosts` file
- [ ] SSL certificates generated for all domains (`bin/setup-ssl` for each domain)
- [ ] Containers restarted (`bin/restart`)
- [ ] Main site accessible at `https://aurora.local`
- [ ] French store accessible at `https://aurora.fr.local`
- [ ] Irish store accessible at `https://aurora.ie.local`
- [ ] Italian store accessible at `https://aurora.it.local`

## 🛠 Development Commands

### Essential Commands

```bash
# Start/stop containers
bin/start --no-dev
bin/stop
bin/restart

# Access container shell
bin/bash

# Run Magento CLI commands
bin/magento <command>

# Clear cache
bin/cache-clean

# View logs
bin/log
```

### Database Operations

```bash
# Access MySQL
bin/mysql

# Backup database
bin/mysqldump

# Restore database (replace with your dump file)
bin/mysql < dump.sql
```

### Development Tools

```bash
# Code quality checks
bin/phpcs
bin/phpcbf

# Static analysis
bin/analyse

# Run tests
bin/test

# Enable/disable Xdebug
bin/xdebug debug
bin/xdebug off
```

## 📁 Project Structure

```
aurora-project/
├── bin/                    # Docker helper scripts
├── env/                    # Environment configuration
│   ├── db.env             # Database settings
│   ├── magento.env        # Magento admin settings
│   └── phpfpm.env         # PHP-FPM configuration
├── src/                    # Magento source code (downloaded separately)
├── compose.yaml           # Main Docker Compose configuration
├── compose.dev.yaml       # Development overrides
├── Makefile              # Convenient command shortcuts
└── README.md             # This file
```

## 🔧 Configuration

### Environment Variables

Key environment files to configure:

- **`env/db.env`**: Database connection settings
- **`env/magento.env`**: Magento admin user settings
- **`env/phpfpm.env`**: PHP-FPM configuration

### Multi-Domain Store Setup

This project is configured with a multi-store setup:

#### **Store Configuration:**
- **Main Website (UK)**: `aurora.local` - English store with GBP currency
- **EU Website**: 
  - `aurora.fr.local` - French store with EUR currency
  - `aurora.ie.local` - Irish store with GBP currency  
  - `aurora.it.local` - Italian store with EUR currency

#### **Store Structure:**
```
UK Website (aurora.local)
├── English Store (en) - Default store
└── Irish Store (ie)

EU Website
├── French Store (fr)
└── Italian Store (it)
```

#### **Data Patches Applied:**
- **AddWebsitesAndStores**: Creates the multi-store structure and sets website-level currencies
- **UpdateStoreBaseUrls**: Configures domain-specific URLs for each store
- **ConfigureStoreLocales**: Sets up locale configuration for each store

#### **Store Locales:**
- **English Store (en)**: `en_GB` (UK English)
- **Irish Store (ie)**: `en_IE` (Irish English)
- **French Store (fr)**: `fr_FR` (French)
- **Italian Store (it)**: `it_IT` (Italian)

#### **Static Content Deployment:**
The setup automatically configures static content deployment for all locales:
```bash
bin/clinotty bin/magento setup:static-content:deploy -f
```
This command now deploys static content for all configured locales: `en_GB`, `en_IE`, `fr_FR`, `it_IT`, and `en_US` (admin).

### Domain Setup

To add additional domains:

1. **Add domain to `/etc/hosts`**:
   ```bash
   sudo nano /etc/hosts
   # Add: 127.0.0.1 your-domain.local
   ```

2. **Generate SSL certificate**:
   ```bash
   bin/setup-ssl your-domain.local
   ```

3. **Restart containers**:
   ```bash
   bin/restart
   ```

### Cron Job Configuration

When configuring cron jobs in Magento, you'll need to set up proper cron expressions. Use [crontab.guru](https://crontab.guru/) to help you create the correct cron schedule expressions.

#### Common Cron Patterns

| Description | Cron Expression | Example |
|-------------|----------------|---------|
| Every minute | `* * * * *` | Run every minute |
| Every 5 minutes | `*/5 * * * *` | Run every 5 minutes |
| Every hour | `0 * * * *` | Run at the top of every hour |
| Every day at midnight | `0 0 * * *` | Run daily at 12:00 AM |
| Every day at 2:30 AM | `30 2 * * *` | Run daily at 2:30 AM |
| Every Monday at 9 AM | `0 9 * * 1` | Run every Monday at 9:00 AM |
| Every weekday at 6 PM | `0 18 * * 1-5` | Run Monday-Friday at 6:00 PM |

#### Using Crontab.guru

1. **Visit [crontab.guru](https://crontab.guru/)**
2. **Enter your desired schedule** in the input field
3. **Get instant feedback** on what your cron expression means
4. **Copy the expression** for use in your Magento cron configuration

#### Magento Cron Configuration

To configure cron jobs in Magento:

1. **Access Admin Panel** → System → Configuration → Advanced → System → Cron (Scheduled Tasks)
2. **Set up cron groups** with appropriate schedules
3. **Use the cron expressions** generated by crontab.guru

Example cron configuration in `app/code/Vendor/Module/etc/crontab.xml`:
```xml
<config>
    <group id="default">
        <job name="vendor_module_sync_data" instance="Vendor\Module\Cron\SyncData" method="execute">
            <schedule>*/15 * * * *</schedule>
        </job>
    </group>
</config>
```

> **💡 Tip**: Always test your cron expressions using [crontab.guru](https://crontab.guru/) before implementing them in your Magento configuration to ensure they run at the expected times.

## 🎨 Frontend Development

### Theme Development

1. **Create a new theme** in `src/app/design/frontend/VendorName/theme-name`
2. **Set up Grunt** for live reload:
   ```bash
   bin/setup-grunt
   bin/grunt watch
   ```
3. **Install LiveReload browser extension** for automatic page refresh

### CSS/JS Development

- CSS files: `src/app/design/frontend/VendorName/theme-name/web/css/`
- JavaScript: `src/app/design/frontend/VendorName/theme-name/web/js/`
- Templates: `src/app/design/frontend/VendorName/theme-name/Magento_Theme/`

## 🧪 Testing

### Unit Tests
```bash
bin/test unit
```

### Integration Tests
```bash
bin/test integration
```

## 📊 Monitoring & Debugging

### Performance Profiling
- **SPX Profiler**: `https://aurora.local/?SPX_UI_URI=/`
- **Xdebug**: Configure your IDE for remote debugging

### Logs
```bash
# View all logs
bin/log

# View specific log files
bin/log system.log
bin/log exception.log
```

### Database Access
```bash
# MySQL CLI
bin/mysql

# Redis CLI
bin/redis
```

## 🔐 Security

### SSL Certificates
```bash
# Generate SSL certificates
bin/setup-ssl aurora.local

# Generate CA certificate
bin/setup-ssl-ca
```

### Authentication
- Update `env/magento.env` with secure admin credentials
- Change default database passwords in `env/db.env`

## 🚀 Deployment

### Production Considerations

1. **Update environment files** with production values
2. **Set proper file permissions**:
   ```bash
   bin/fixperms
   ```
3. **Enable production mode**:
   ```bash
   bin/magento deploy:mode:set production
   ```

### Backup Strategy

```bash
# Database backup
bin/mysqldump > backup-$(date +%Y%m%d).sql

# Full project backup
tar -czf aurora-backup-$(date +%Y%m%d).tar.gz src/ env/
```

## 🆘 Troubleshooting

### Common Issues

1. **Port conflicts**: Check if ports 80, 443, 3306, 6379 are available
2. **Permission issues**: Run `bin/fixperms`
3. **Container won't start**: Check Docker Desktop is running
4. **Database connection**: Verify `env/db.env` settings

### Reset Environment

```bash
# Stop and remove all containers
bin/removeall

# Start fresh
bin/start
bin/setup-install aurora.local
```

## 📚 Additional Resources

- [Mark Shust's Docker Magento Documentation](https://github.com/markshust/docker-magento)
- [Magento 2 Developer Documentation](https://devdocs.magento.com/)
- [Magento 2 Frontend Development Guide](https://devdocs.magento.com/guides/v2.4/frontend-dev-guide/bk-frontend-dev-guide.html)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

**Happy coding! 🎉**

For questions or support, please open an issue in the repository.
