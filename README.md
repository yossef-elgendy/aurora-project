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
   
   # Edit the files with your own values
   # Especially change passwords in db.env, magento.env, and rabbitmq.env
   ```

3. **Start the Docker environment (without development tools)**
   ```bash
   bin/start --no-dev
   ```

4. **Copy files to container and install dependencies**
   ```bash
   bin/copytocontainer --all
   bin/composer install
   ```

5. **Install Magento with main domain**
   ```bash
   bin/setup-install aurora.local
   ```

6. **Setup additional domains**
   ```bash
   bin/setup-domain aurora.fr.local
   bin/setup-domain aurora.ie.local
   bin/setup-domain aurora.it.local
   ```

7. **Restart containers to apply all changes**
   ```bash
   bin/restart
   ```

8. **Access your sites**
   - **Main Store (English)**: `https://aurora.local`
   - **French Store**: `https://aurora.fr.local`
   - **Irish Store**: `https://aurora.ie.local`
   - **Italian Store**: `https://aurora.it.local`
   - **Admin Panel**: `https://aurora.local/admin`
   - **Admin credentials**: As configured in `env/magento.env`

## 📋 Complete Setup Checklist for New Developers

- [ ] Docker Desktop installed and running
- [ ] Git repository cloned
- [ ] Environment files created from examples
- [ ] Environment files edited with secure passwords
- [ ] Containers started (`bin/start --no-dev`)
- [ ] Files copied to container (`bin/copytocontainer --all`)
- [ ] Composer dependencies installed (`bin/composer install`)
- [ ] Magento setup completed (`bin/setup-install aurora.local`)
- [ ] Additional domains configured (`bin/setup-domain` for each domain)
- [ ] Containers restarted (`bin/restart`)
- [ ] Main site accessible at `https://aurora.local`
- [ ] French store accessible at `https://aurora.fr.local`
- [ ] Irish store accessible at `https://aurora.ie.local`
- [ ] Italian store accessible at `https://aurora.it.local`

## 🛠 Development Commands

### Essential Commands

```bash
# Start/stop containers
bin/start
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

### Domain Setup

To add additional domains:

1. Run `bin/setup-domain <your-domain>`
2. Restart containers: `bin/restart`

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
