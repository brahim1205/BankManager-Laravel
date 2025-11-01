
  APP_ENV=production \
  APP_KEY=$(openssl rand -base64 32) \
 APP_DEBUG=false \
   DB_CONNECTION=pgsql 
   DB_HOST=$BANKMANAGER_DB_HOST 
   DB_PORT=$BANKMANAGER_DB_PORT 
   DB_DATABASE=$BANKMANAGER_DB_DATABASE 
   DB_USERNAME=$BANKMANAGER_DB_USER 
   DB_PASSWORD=$BANKMANAGER_DB_PASSWORD 
   L5_SWAGGER_USE_ABSOLUTE_PATH=true 
   MAIL_MAILER=log 
   QUEUE_CONNECTION=sync 
  bankmanager-api \
  sh -c "php artisan migrate --force && php artisan db:seed --force && php artisan l5-swagger:generate && php artisan serve --host 0.0.0.0 --port 8000"
