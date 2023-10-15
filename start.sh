 #!/usr/bin/env bash
 set -e
 find /var/www -not -user www-data | grep -q /var && {
      echo "ERROR: /var/www or one or more of it's children is not owned by www-data:www-data"
      exit 1
 }
 apachectl -D FOREGROUND
