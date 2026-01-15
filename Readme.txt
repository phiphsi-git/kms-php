kms-php/
├─ public/
│  ├─ index.php          # Front-Controller/Router
│  ├─ assets/styles.css  # schlichtes, professionelles Styling
│  └─ logo.png           # optional: lade dein Logo hierhin (oder nutze die URL)
├─ src/
│  ├─ Config.php
│  ├─ DB.php
│  ├─ Auth.php
│  ├─ Csrf.php
│  ├─ PasswordPolicy.php
│  ├─ Middleware.php
│  └─ UserRepo.php
├─ views/
│  ├─ layout.php
│  ├─ login.php
│  ├─ dashboard.php
│  └─ users.php          # einfache Admin-Ansicht zum Anlegen von Nutzern
├─ storage/customers/{customer_id}/
│  ├─ files/ (Anleitungen/Schemas etc.)
│  └─ reports/ (PDF-Reports)
└─ database.sql          # Schema zum Import in MySQL/MariaDB


PDF Report:
Lege mindestens storage/templates/report_base.html an.
Wenn du für einzelne Kunden ein abweichendes Layout willst:
storage/customers/{id}/report_base.html erstellen – das zieht automatisch vor.