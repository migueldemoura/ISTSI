FROM debian:jessie

RUN apt-get update && apt-get install -y cron gettext-base
RUN mkdir /backup

COPY main.sh backup.sh restore.sh /
RUN cd / && chmod +x main.sh backup.sh restore.sh

CMD ["/main.sh"]
