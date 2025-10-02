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

3. **Start the Docker environment**
   ```bash
   make start
   ```

4. **Install Composer dependencies**
   ```bash
   make composer install
   ```

5. **Run the setup process**
   ```bash
   make setup aurora.local
   ```

6. **Add domain to hosts file**
   ```bash
   # macOS/Linux
   echo "127.0.0.1 aurora.local" | sudo tee -a /etc/hosts
   
   # Windows (run as Administrator)
   # Edit C:\Windows\System32\drivers\etc\hosts
   # Add: 127.0.0.1 aurora.local
   ```

7. **Access your site**
   - Frontend: `https://aurora.local`
   - Admin: `https://aurora.local/admin`
   - Admin credentials: As configured in `env/magento.env`

## 📋 Complete Setup Checklist for New Developers

- [ ] Docker Desktop installed and running
- [ ] Git repository cloned
- [ ] Environment files created from examples
- [ ] Environment files edited with secure passwords
- [ ] Domain added to hosts file
- [ ] Containers started (`make start`)
- [ ] Composer dependencies installed (`make composer install`)
- [ ] Magento setup completed (`make setup aurora.local`)
- [ ] Site accessible at `https://aurora.local`

## 🛠 Development Commands

### Essential Commands

```bash
# Start/stop containers
make start
make stop
make restart

# Access container shell
make bash

# Run Magento CLI commands
make magento <command>

# Clear cache
make cache-clean

# View logs
make log
```

### Database Operations

```bash
# Access MySQL
make mysql

# Backup database
make mysqldump

# Restore database (replace with your dump file)
make mysql < dump.sql
```

### Development Tools

```bash
# Code quality checks
make phpcs
make phpcbf

# Static analysis
make analyse

# Run tests
make test

# Enable/disable Xdebug
make xdebug debug
make xdebug off
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

### Domain Setup

The project is configured to use `aurora.local` by default. To change this:

1. Update `env/magento.env` with your domain
2. Run `make setup-domain <your-domain>`
3. Add the domain to your hosts file:
   ```bash
   echo "127.0.0.1 aurora.local" >> /etc/hosts
   ```

## 🎨 Frontend Development

### Theme Development

1. **Create a new theme** in `src/app/design/frontend/VendorName/theme-name`
2. **Set up Grunt** for live reload:
   ```bash
   make setup-grunt
   make grunt watch
   ```
3. **Install LiveReload browser extension** for automatic page refresh

### CSS/JS Development

- CSS files: `src/app/design/frontend/VendorName/theme-name/web/css/`
- JavaScript: `src/app/design/frontend/VendorName/theme-name/web/js/`
- Templates: `src/app/design/frontend/VendorName/theme-name/Magento_Theme/`

## 🧪 Testing

### Unit Tests
```bash
make test unit
```

### Integration Tests
```bash
make test integration
```

## 📊 Monitoring & Debugging

### Performance Profiling
- **SPX Profiler**: `https://aurora.local/?SPX_UI_URI=/`
- **Xdebug**: Configure your IDE for remote debugging

### Logs
```bash
# View all logs
make log

# View specific log files
make log system.log
make log exception.log
```

### Database Access
```bash
# MySQL CLI
make mysql

# Redis CLI
make redis
```

## 🔐 Security

### SSL Certificates
```bash
# Generate SSL certificates
make setup-ssl aurora.local

# Generate CA certificate
make setup-ssl-ca
```

### Authentication
- Update `env/magento.env` with secure admin credentials
- Change default database passwords in `env/db.env`

## 🚀 Deployment

### Production Considerations

1. **Update environment files** with production values
2. **Set proper file permissions**:
   ```bash
   make fixperms
   ```
3. **Enable production mode**:
   ```bash
   make magento deploy:mode:set production
   ```

### Backup Strategy

```bash
# Database backup
make mysqldump > backup-$(date +%Y%m%d).sql

# Full project backup
tar -czf aurora-backup-$(date +%Y%m%d).tar.gz src/ env/
```

## 🆘 Troubleshooting

### Common Issues

1. **Port conflicts**: Check if ports 80, 443, 3306, 6379 are available
2. **Permission issues**: Run `make fixperms`
3. **Container won't start**: Check Docker Desktop is running
4. **Database connection**: Verify `env/db.env` settings

### Reset Environment

```bash
# Stop and remove all containers
make removeall

# Start fresh
make start
make setup aurora.local
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
