#/usr/bin/aws s3 sync --delete /var/www/html/sitemap/public/runthrough  s3://sma-sitemaps/sitemap/`curl http://169.254.169.254/latest/meta-data/security-groups`/runthrough

#/usr/bin/aws s3 sync --delete /var/www/html/sitemap/public/runforcharity  s3://sma-sitemaps/sitemap/`curl http://169.254.169.254/latest/meta-data/security-groups`/runforcharity

#/usr/bin/aws s3 sync --delete /var/www/html/sitemap/public/runninggrandprix  s3://sma-sitemaps/sitemap/`curl http://169.254.169.254/latest/meta-data/security-groups`/runninggrandprix

echo "We are now directly storing these files in S3"
