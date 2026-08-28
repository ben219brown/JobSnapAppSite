#!/bin/bash

# Navigate to your images folder
cd /var/www/jobsnapapp.com/html/images

# Rename images: replace spaces with underscores and make lowercase
for file in *; do
  newname=$(echo "$file" | tr '[:upper:]' '[:lower:]' | tr ' ' '_')
  if [[ "$file" != "$newname" ]]; then
    mv "$file" "$newname"
    echo "Renamed '$file' → '$newname'"
  fi
done

# Navigate to logo folder
cd /var/www/jobsnapapp.com/html/logo

for file in *; do
  newname=$(echo "$file" | tr '[:upper:]' '[:lower:]' | tr ' ' '_')
  if [[ "$file" != "$newname" ]]; then
    mv "$file" "$newname"
    echo "Renamed '$file' → '$newname'"
  fi
done
