# restic kumar connector

This is a restic connector for kumar. It allows you to check if you backups did run in time.

## Usage

### Running the Container

Just run the container, ideally using compose and expose port 80, maybe use something like traffic to make it https.
You could add a volume to /var/www/data to persist the data.
This is not necessary, but a fresh container will not report functional backups until you reported something.
If it's nor prevented on a network level for the world to submit data, you might whant to set RKC_USER and RKC_PASS to prevent random people from submitting data.
You need to use these credentials when reporting to the webservice.

### Reporting

To report your you need to post the output of `restic snapshots` to the webservice, eg:

```bash
restic snapshots | curl -X POST -d @- http://restic_kumar_reporter/
```

or

```bash
restic snapshots | curl -X POST --data-binary @- -u "$USER:$PASS" http://restic_kumar_reporter/
```

when RKC_USER and RKC_PASS are set.

### Checking with kumar

Just point kumar to your webservice.
The Output looks something like this:

```
BACKUP|HOST|PATH|STATUS
BACKUP|host1|/opt/mailcow|OK
BACKUP|host1|/var/lib/docker/volumes|OK
BACKUP|host2|/opt/docker|OK
BACKUP|host2|/var/lib/docker/volumes|OK
BACKUP|host3|/opt/docker|TOO_OLD
BACKUP|host3|/var/lib/docker/volumes|OK
BACKUP|host4|/opt/docker|OK
BACKUP|host4|/var/lib/docker/volumes|OK
BACKUP|host5|/opt/docker|TOO_OLD
BACKUP|host5|/var/lib/docker/volumes|TOO_OLD

BACKUP|HOST|STATUS
BACKUP|host1|OK
BACKUP|host2|OK
BACKUP|host3|TOO_OLD
BACKUP|host4|OK
BACKUP|host5|TOO_OLD
```

output is always sorted by host and path. so you might check a whole block of hosts at once.

A Backup is okay if it is not older than X hours (default 28).
You can change this by requesting with `?maxage=XX` where XX is the maximum age in hours.
