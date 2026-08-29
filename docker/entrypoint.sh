#!/bin/bash
set -e

# ---------------------------------------------------------------------------
# Entrypoint for the OpenWebSoccer-Sim Docker image.
#
# Starts the cron daemon (with the recommended job schedules) and then
# launches Apache in the foreground.
#
# Environment variables:
#   WEBSOCCER_CRON_ENABLED   Set to "0" to disable cron jobs.  Default: 1
#   WEBSOCCER_JOB_SECTOKEN   Security key that matches the webjobexecution_key
#                            setting in the AdminCenter.  Default: -
# ---------------------------------------------------------------------------

# Ensure the cache directory and any files already present (e.g. left over
# from a previous container start where cron ran as root) are owned by
# www-data so that Apache can write to them.
chown -R www-data:www-data /var/www/html/cache

if [ "${WEBSOCCER_CRON_ENABLED:-1}" = "1" ]; then
    SECTOKEN="${WEBSOCCER_JOB_SECTOKEN:--}"

    # Write the crontab with the two recommended job schedules:
    #   1. every minute       â€“ simulate open matches
    #   2. every 15 minutes    â€“ all other jobs
    #
    # Jobs run as www-data (via su) so that cache files they create are
    # owned by www-data and can be overwritten by Apache (also www-data).
    # The output redirect is handled by root's shell so it can write to
    # /proc/1/fd/1 (container stdout), which www-data cannot do directly.
    cat <<EOF | crontab -
* * * * * su -s /bin/sh www-data -c '/usr/local/bin/php /var/www/html/webservices/executeJob.php sectoken=${SECTOKEN} jobid=sim' >> /proc/1/fd/1 2>&1
*/15 * * * * su -s /bin/sh www-data -c '/usr/local/bin/php /var/www/html/webservices/executeJob.php sectoken=${SECTOKEN} jobid=addplyr,extransf,usractv,stats,stadium' >> /proc/1/fd/1 2>&1
EOF

    # Start the cron daemon in the background
    cron
    echo "Cron daemon started (security key: ${SECTOKEN})"
else
    echo "Cron daemon disabled (WEBSOCCER_CRON_ENABLED=0)"
fi

# Start Apache in the foreground
exec apache2-foreground
