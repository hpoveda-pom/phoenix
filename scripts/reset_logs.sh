#!/bin/bash

# Limpiar logs de /var/log/*.log mayores a 100MB
find /var/log -type f -name "*.log" -size +100M -exec truncate -s 0 {} \;

# Limpiar archivos temporales en /tmp
rm -rf /tmp/*

# Limpiar archivos temporales en /var/tmp
rm -rf /var/tmp/*

# Limpiar archivos temporales en /var/tmp
rm -rf ../tmp/*

# Limpiar archivos temporales en /var/tmp
rm -rf /var/www/phoenix/tmp/*
