# FashionDialZena

Katalog fashion FashionDialZena — tampilan depan & belakang, order via WhatsApp.

Repo GitHub: https://github.com/pribadisendiri84/fashiondialzena

---

## Push ke GitHub (Manual)

Repo ini memakai **SSH key terpisah** dari akun Git Bukalapak di laptop.

### 1. Masuk ke folder project

```bash
cd ~/bukalapak/Noted/fashiondialzena
```

### 2. Pastikan identitas Git untuk repo ini (lokal)

Jangan pakai `--global`, supaya tidak mengubah repo kerja.

```bash
git config user.name "pribadisendiri84"
git config user.email "pribadisendiri@gmail.com"
```

Cek:

```bash
git config user.email
git config --global user.email
```

- Lokal → email personal
- Global → boleh tetap email Bukalapak

### 3. Pastikan SSH key personal sudah ada

Key khusus repo personal (sama dengan dedet18):

```bash
ls ~/.ssh/id_ed25519_dedet18 ~/.ssh/id_ed25519_dedet18.pub
```

Kalau belum ada, buat:

```bash
ssh-keygen -t ed25519 -f ~/.ssh/id_ed25519_dedet18 -C "pribadisendiri84-dedet18"
```

Copy public key:

```bash
cat ~/.ssh/id_ed25519_dedet18.pub
```

Tambahkan ke GitHub → **Settings → SSH and GPG keys → New SSH key**  
https://github.com/settings/keys

### 4. Tes koneksi SSH (pakai key personal)

```bash
ssh -i ~/.ssh/id_ed25519_dedet18 -o IdentitiesOnly=yes -T git@github.com
```

Kalau berhasil, muncul:

```text
Hi pribadisendiri84! You've successfully authenticated...
```

### 5. Pastikan remote repo benar

```bash
git remote -v
```

Harus menunjuk ke:

```text
git@github.com:pribadisendiri84/fashiondialzena.git
```

Repo ini sudah diset pakai SSH key khusus lewat:

```bash
git config core.sshcommand
```

### 6. Commit & push

```bash
git add .
git status
git commit -m "Update katalog FashionDialZena"
git push origin main
```

Push pertama kali (kalau branch belum pernah di-push):

```bash
git push -u origin main
```

---

## Cek sedang pakai Git yang mana

Jalankan di folder `fashiondialzena`:

```bash
echo "Name: $(git config user.name)"
echo "Email: $(git config user.email)"
echo "Remote: $(git remote get-url origin)"
echo "SSH: $(git config core.sshcommand)"
```

---

## Struktur file

```text
fashiondialzena/
├── index.html      # Katalog fashion
├── CNAME           # Custom domain GitHub Pages
├── README.md       # Panduan ini
└── .gitignore
```

---

## Deploy (GitHub Pages)

1. Buka repo → **Settings → Pages**
2. Source: **Deploy from a branch**
3. Branch: **main** / folder **/ (root)**
4. Save

Website:

```text
https://fashiondialzena.com/
```

Custom domain GitHub Pages memakai file `CNAME` berisi `fashiondialzena.com`. Kalau masih terbuka lewat `github.io`, halaman akan redirect ke domain utama.
