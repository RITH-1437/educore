FROM node:22-alpine

ENV CHOKIDAR_USEPOLLING=true

WORKDIR /app

COPY entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 5173

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]