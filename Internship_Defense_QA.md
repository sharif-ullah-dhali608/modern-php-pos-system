# 🎓 ইন্টার্নশিপ ডিফেন্স — প্রশ্ন ও উত্তর
### প্রজেক্ট: **Velocity POS — Modern Point of Sale System**
> **ভাষা:** PHP, MySQL, JavaScript (jQuery), TailwindCSS  
> **সার্ভার:** Apache (MAMP), Database: MySQL  
> **ডেভেলপার:** শরীফউল্লাহ ঢালী  

---

## 📌 বিভাগ ১: প্রজেক্ট পরিচিতি ও সাধারণ প্রশ্ন

---

### ❓ প্রশ্ন ১: তোমার প্রজেক্টের নাম কী এবং এটি কী ধরনের সিস্টেম?

**✅ উত্তর:**  
আমার প্রজেক্টের নাম **Velocity POS (Point of Sale System)**। এটি একটি ওয়েব-ভিত্তিক আধুনিক বিক্রয় ব্যবস্থাপনা সিস্টেম। এই সিস্টেমে একটি ব্যবসার সমস্ত ক্রয়-বিক্রয়, ইনভেন্টরি, গ্রাহক ব্যবস্থাপনা, আয়-ব্যয় হিসাব, লোন, কিস্তি, রিপোর্ট ইত্যাদি সুন্দরভাবে পরিচালনা করা যায়। সিস্টেমটি মাল্টি-স্টোর সাপোর্ট করে, অর্থাৎ একটি একাউন্ট দিয়ে একাধিক শাখার ব্যবসা পরিচালনা করা সম্ভব।

---

### ❓ প্রশ্ন ২: এই প্রজেক্ট তৈরিতে কোন কোন প্রযুক্তি ব্যবহার করেছ?

**✅ উত্তর:**  
আমি নিচের প্রযুক্তিগুলো ব্যবহার করেছি:

| স্তর | প্রযুক্তি |
|------|-----------|
| **Backend** | PHP (Core PHP, কোনো ফ্রেমওয়ার্ক নেই) |
| **Database** | MySQL (InnoDB Engine, utf8mb4 charset) |
| **Frontend** | HTML5, TailwindCSS, Vanilla JavaScript, jQuery |
| **UI লাইব্রেরি** | Font Awesome 6, SweetAlert2, DataTables, Select2, ApexCharts |
| **সার্ভার** | Apache (MAMP — Mac, Apache, MySQL, PHP) |
| **URL Routing** | Apache `.htaccess` দিয়ে Clean URL |
| **Print** | Thermal/Network Printer সাপোর্ট |

---

### ❓ প্রশ্ন ৩: প্রজেক্টে কী কী মডিউল আছে?

**✅ উত্তর:**  
প্রজেক্টে মোট **৩৪টি মডিউল** আছে। প্রধান মডিউলগুলো হলো:

1. **Dashboard** — সেলস ট্রেন্ড, রেভিনিউ, অর্ডার, গ্রাফ
2. **POS (Point of Sale)** — দ্রুত বিক্রয়, বারকোড স্ক্যান, একাধিক পেমেন্ট মেথড
3. **Sales Management** — বিক্রয় তালিকা, ইনভয়েস, রিটার্ন
4. **Purchase Management** — পণ্য ক্রয়, সাপ্লায়ার পেমেন্ট
5. **Product Management** — পণ্য যোগ/সম্পাদনা, ব্যারকোড, স্টক ট্র্যাকিং
6. **Category, Brand, Unit** — পণ্য শ্রেণীবিভাগ
7. **Customer Management** — গ্রাহক প্রোফাইল, ক্রেডিট লিমিট, রিওয়ার্ড পয়েন্ট
8. **Supplier Management** — সাপ্লায়ার তথ্য, ড্যু ট্র্যাকিং
9. **Inventory/Stock Transfer** — এক শাখা থেকে অন্য শাখায় স্টক ট্রান্সফার
10. **Quotation** — বিক্রয় কোটেশন তৈরি
11. **Installment** — কিস্তিতে বিক্রয়
12. **Loan Management** — লোন গ্রহণ/প্রদান ট্র্যাকিং
13. **Expenditure** — খরচ ক্যাটাগরি ও ব্যয় ট্র্যাকিং
14. **Accounting (Cashbook, Profit/Loss, Bank)** — সম্পূর্ণ হিসাব বিভাগ
15. **Gift Card** — গিফট কার্ড তৈরি, টপআপ, বিক্রয়
16. **Reports (১৭টি রিপোর্ট)** — সেলস, পার্চেজ, স্টক, ট্যাক্স, ব্যালেন্স শীট ইত্যাদি
17. **User & Role Management** — ইউজার রোল, পারমিশন কন্ট্রোল
18. **Multi-Store Management** — একাধিক শাখা পরিচালনা
19. **Printer Management** — Thermal/Network প্রিন্টার কনফিগারেশন
20. **Tax Rate Management** — কর হার সেটআপ
21. **Currency Management** — বিভিন্ন মুদ্রা সাপোর্ট
22. **Payment Methods** — বিভিন্ন পেমেন্ট মেথড কনফিগারেশন
23. **Documentation** — সিস্টেম গাইড ও ডকুমেন্টেশন

---

### ❓ প্রশ্ন ৪: তুমি এই প্রজেক্ট কেন তৈরি করলে? এর প্রয়োজনীয়তা কী?

**✅ উত্তর:**  
এই প্রজেক্টটি আমি **Southeast University (SEU)-র IT Department/Office-এর প্রয়োজনে ইন্টার্নশিপ চলাকালীন** তৈরি করেছি। অফিসে বিদ্যমান বিক্রয় ও ইনভেন্টরি ট্র্যাকিং ম্যানুয়ালি বা অসম্পূর্ণ সফটওয়্যার দিয়ে হচ্ছিল। তাই একটি সম্পূর্ণ ডিজিটাল সমাধান দরকার ছিল।

**বাস্তব প্রয়োজনীয়তাগুলো:**
- পণ্য ক্রয়-বিক্রয়ের রেকর্ড ডিজিটালি সংরক্ষণ করা
- গ্রাহক ও সাপ্লায়ারের হিসাব একটি জায়গায় রাখা
- আয়-ব্যয়ের রিয়েল-টাইম রিপোর্ট পাওয়া
- মাল্টি-ব্রাঞ্চ পরিচালনার সুবিধা দেওয়া
- কিস্তি ও লোনের মতো স্থানীয় প্রেক্ষাপটের ফিচার অন্তর্ভুক্ত করা

সংক্ষেপে, **SEU IT Office-এর দৈনন্দিন ব্যবসায়িক কার্যক্রম সম্পূর্ণ ডিজিটাইজ করাই ছিল এই প্রজেক্টের মূল লক্ষ্য।**

---

## 📌 বিভাগ ২: ডেটাবেজ ডিজাইন সম্পর্কিত প্রশ্ন

---

### ❓ প্রশ্ন ৫: তোমার ডেটাবেজের নাম কী এবং কতগুলো টেবিল আছে?

**✅ উত্তর:**  
ডেটাবেজের নাম **`pos_system`**। এতে **৩০টিরও বেশি টেবিল** আছে। প্রধান টেবিলগুলো হলো:

- `stores` — শাখার তথ্য
- `products` — পণ্যের তথ্য
- `categories`, `brands`, `units` — পণ্য শ্রেণীবিভাগ
- `customers`, `suppliers` — গ্রাহক ও সাপ্লায়ার
- `selling_info`, `selling_item` — বিক্রয় হেডার ও আইটেম
- `purchase_info`, `purchase_item` — ক্রয় হেডার ও আইটেম
- `expenses`, `expense_category` — ব্যয়ের তথ্য
- `income_sources` — আয়ের উৎস
- `bank_accounts`, `bank_transaction_info`, `bank_transaction_price` — ব্যাংক হিসাব
- `loans` — লোনের তথ্য
- `installments` — কিস্তির তথ্য
- `giftcards`, `giftcard_topups` — গিফট কার্ড
- `quotations`, `quotation_items` — কোটেশন
- `transfers` — স্টক ট্রান্সফার
- `users` — ব্যবহারকারী তথ্য
- `payment_methods`, `payment_store_map` — পেমেন্ট মেথড
- `currencies`, `taxrates` — মুদ্রা ও কর হার
- `product_store_map` — পণ্যের স্টোরভিত্তিক স্টক
- `boxes`, `printers` — গুদাম বাক্স ও প্রিন্টার

---

### ❓ প্রশ্ন ৬: তোমার ডেটাবেজে Foreign Key ব্যবহার করেছ কেন?

**✅ উত্তর:**  
হ্যাঁ, আমি InnoDB ইঞ্জিন ব্যবহার করেছি এবং Foreign Key কনস্ট্রেইন্ট দিয়ে টেবিলগুলোর মধ্যে সম্পর্ক তৈরি করেছি। এর কারণগুলো হলো:

1. **রেফারেনশিয়াল ইন্টিগ্রিটি** — যদি কোনো পণ্য ডিলিট হয়, তার সাথে সংযুক্ত সেলস আইটেম, ক্রয় আইটেম ইত্যাদিও ক্যাসকেড ডিলিট বা NULL সেট হয়ে যায়, ডেটা অসামঞ্জস্যপূর্ণ হয় না।
2. **ডেটার নির্ভুলতা** — উদাহরণস্বরূপ, `products.category_id` অবশ্যই `categories.id`-এ বিদ্যমান থাকতে হবে।
3. **ব্যাকএন্ড কোড সিম্পল রাখা** — অনেক ভ্যালিডেশন DB-স্তরেই হয়ে যায়।

উদাহরণ:
```sql
FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL
```

---

### ❓ প্রশ্ন ৭: মাল্টি-স্টোর সাপোর্ট ডেটাবেজে কীভাবে ইম্পলিমেন্ট করেছ?

**✅ উত্তর:**  
মাল্টি-স্টোর সাপোর্টের জন্য আমি **Pivot Table (জাংশন টেবিল)** পদ্ধতি ব্যবহার করেছি। প্রতিটি রিসোর্স স্টোরের সাথে ম্যাপ করা আছে। যেমন:

| Pivot টেবিল | উদ্দেশ্য |
|-------------|---------|
| `product_store_map` | কোন পণ্যের কোন স্টোরে কতটুকু স্টক আছে |
| `customer_stores_map` | কোন গ্রাহক কোন স্টোরের |
| `supplier_stores_map` | সাপ্লায়ার-স্টোর সম্পর্ক |
| `payment_store_map` | কোন স্টোরে কোন পেমেন্ট মেথড চালু |
| `category_store_map` | ক্যাটাগরি-স্টোর ম্যাপিং |
| `brand_store` | ব্র্যান্ড-স্টোর ম্যাপিং |
| `bank_account_to_store` | ব্যাংক একাউন্ট-স্টোর সম্পর্ক |

ড্যাশবোর্ডে `$_SESSION['store_id']` ব্যবহার করে প্রতিটি ক্যুয়েরিতে স্টোর ফিল্টার লাগানো হয়।

---

### ❓ প্রশ্ন ৮: `selling_info` এবং `selling_item` টেবিল আলাদা রাখলে কেন?

**✅ উত্তর:**  
এটি **মাস্টার-ডিটেইল (Master-Detail)** ডিজাইন প্যাটার্ন। একটি বিক্রয়ে:

- `selling_info` — একটি রো থাকে (ইনভয়েস নম্বর, গ্রাহক, মোট টাকা, পেমেন্ট স্ট্যাটাস, ডিসকাউন্ট ইত্যাদি)
- `selling_item` — সেই ইনভয়েসের প্রতিটি পণ্যের জন্য আলাদা রো থাকে (পণ্যের নাম, পরিমাণ, মূল্য, ট্যাক্স)

এতে করে:
- একটি ইনভয়েসে অনেকগুলো পণ্য থাকতে পারে
- প্রতিটি পণ্য আলাদাভাবে ট্র্যাক করা যায়
- ডেটাবেজ নরমালাইজড থাকে (3NF)

---

## 📌 বিভাগ ৩: পিএইচপি ও ব্যাকএন্ড প্রশ্ন

---

### ❓ প্রশ্ন ৯: তোমার প্রজেক্টে Session কীভাবে ব্যবহার করেছ?

**✅ উত্তর:**  
আমি PHP Session ব্যবহার করে ইউজার Authentication ও Authorization করেছি। লগইনের পর `$_SESSION['auth']` সেট হয় এবং `$_SESSION['auth_user']` অ্যারেতে ইউজারের নাম, ভূমিকা, ছবি, স্টোর আইডি সংরক্ষিত থাকে। প্রতিটি পেজে `header.php`-এ চেক করা হয়:

```php
if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}
```

`$_SESSION['store_id']` দিয়ে বর্তমান সক্রিয় স্টোর নির্ধারণ করা হয়, যা ড্যাশবোর্ড থেকে সমস্ত কুয়েরিতে ফিল্টার হিসেবে কাজ করে।

---

### ❓ প্রশ্ন ১০: Clean URL বা SEO Friendly URL কীভাবে করেছ?

**✅ উত্তর:**  
Apache-এর `.htaccess` ফাইল ব্যবহার করে `mod_rewrite` দিয়ে Clean URL ইম্পলিমেন্ট করেছি। উদাহরণ:

```
/pos/products/list    →   /pos/products/list_product.php
/pos/sell/list        →   /pos/pos/sell_list.php
/pos/reports/sell     →   /pos/reports/sell_report.php
/pos/login            →   /pos/signin.php
```

এটি করার সুবিধা:
- URL দেখতে পরিষ্কার ও পেশাদার
- `.php` এক্সটেনশন লুকানো যায়
- ইউজার ফ্রেন্ডলি ও নিরাপদ

---

### ❓ প্রশ্ন ১১: ডেটাবেজ ইনজেকশন থেকে রক্ষার জন্য কী করেছ?

**✅ উত্তর:**  
আমি `mysqli_real_escape_string()` এবং `intval()` ফাংশন ব্যবহার করে ব্যবহারকারীর ইনপুট স্যানিটাইজ করেছি। এছাড়া:

1. **`intval()`** — সংখ্যাভিত্তিক ইনপুটের জন্য
2. **`mysqli_real_escape_string()`** — স্ট্রিং ইনপুটের জন্য  
3. **`htmlspecialchars()`** — আউটপুট ডিসপ্লের সময় XSS প্রতিরোধ করতে
4. **Session-based Authentication** — প্রতিটি পেজে session চেক

ভবিষ্যতে Prepared Statement ব্যবহার করা আরও নিরাপদ হবে, যা আমার পরবর্তী আপগ্রেডে যোগ করার পরিকল্পনা আছে।

---

### ❓ প্রশ্ন ১২: `ensure_core_tables()` ফাংশনটি কী করে?

**✅ উত্তর:**  
`config/dbcon.php` ফাইলে `ensure_core_tables()` ফাংশনটি প্রজেক্টের সমস্ত প্রয়োজনীয় টেবিল `CREATE TABLE IF NOT EXISTS` দিয়ে তৈরি করে। এটি মূলত একটি **Auto-Migration** সিস্টেম।

এর সুবিধা হলো:
- নতুন ডেভেলপার প্রজেক্ট ক্লোন করলে তাকে আলাদা SQL ইম্পোর্ট করতে হয় না
- সার্ভারে প্রথমবার রান করলেই ডেটাবেজ তৈরি হয়ে যায়
- পুরানো ইন্সটলেশনে নতুন কলাম যোগ করতে `ALTER TABLE` দিয়ে মাইগ্রেশনও হ্যান্ডল করা হয়েছে

---

### ❓ প্রশ্ন ১৩: Permission System কীভাবে কাজ করে?

**✅ উত্তর:**  
`includes/permission_helper.php`-এ `check_user_permission()` ফাংশন আছে। প্রতিটি ইউজারের রোল অনুযায়ী নির্দিষ্ট পারমিশন নির্ধারণ করা আছে। ড্যাশবোর্ড থেকে শুরু করে প্রতিটি বাটন, কার্ড ও মেনুতে এই চেক ব্যবহার করা হয়েছে:

```php
<?php if(check_user_permission('view_pos_shortcut_dashboard')): ?>
    <a href="/pos/pos/">POS</a>
<?php endif; ?>
```

এতে বিভিন্ন রোলের ইউজার (Admin, Manager, Cashier) বিভিন্ন ফিচার দেখতে পায়।

---

## 📌 বিভাগ ৪: মডিউল-নির্দিষ্ট প্রশ্ন

---

### ❓ প্রশ্ন ১৪: POS মডিউলে একটি বিক্রয় কীভাবে সম্পন্ন হয়?

**✅ উত্তর:**  
POS স্ক্রিনে একটি বিক্রয়ের প্রবাহ:

1. **পণ্য নির্বাচন** — বারকোড স্ক্যান বা সার্চ করে পণ্য কার্টে যোগ করা হয়
2. **পরিমাণ ও মূল্য** — কোয়ান্টিটি পরিবর্তন করা যায়, ডিসকাউন্ট দেওয়া যায়
3. **কাস্টমার নির্বাচন** — গ্রাহক Select2 দিয়ে খোঁজা যায়
4. **পেমেন্ট** — ক্যাশ, মোবাইল ব্যাংকিং, ক্রেডিট কার্ড বা একাধিক পেমেন্ট মেথড ব্যবহার করা যায়
5. **ইনভয়েস জেনারেশন** — বিক্রয় সম্পন্ন হলে ইনভয়েস নম্বর তৈরি হয় এবং প্রিন্ট করা যায়
6. **ডেটাবেজ আপডেট** — `selling_info`, `selling_item`, `product_store_map` (স্টক কমানো), `sell_logs` টেবিল আপডেট হয়

---

### ❓ প্রশ্ন ১৫: Installment (কিস্তি) মডিউল কীভাবে কাজ করে?

**✅ উত্তর:**  
কিস্তি মডিউলে একটি পণ্য বিক্রয়ের সময় কিস্তির শর্ত নির্ধারণ করা হয়:
- কতটি কিস্তিতে পরিশোধ হবে
- প্রতি কিস্তির পরিমাণ কত
- কিস্তির তারিখ কবে

`installment` ফোল্ডারে এই মডিউলের সমস্ত ফাইল আছে। প্রতিটি কিস্তি পরিশোধ হলে সেটা ডেটাবেজে আপডেট হয় এবং বাকি ডিউ পরিসংখ্যান আপডেট হয়।

---

### ❓ প্রশ্ন ১৬: Loan মডিউলে কী কী ফিচার আছে?

**✅ উত্তর:**  
`loan` ফোল্ডারে ৪টি ফাইল আছে:
- **`add_loan.php`** — নতুন লোন যোগ করা
- **`loan_list.php`** — সমস্ত লোনের তালিকা
- **`save_loan.php`** — লোন সেভ করার API
- **`summary_loan.php`** — লোন সারসংক্ষেপ ও পরিসংখ্যান

লোন মডিউল দিয়ে ব্যবসার নেওয়া বা দেওয়া উভয় ধরনের লোন ট্র্যাক করা যায়।

---

### ❓ প্রশ্ন ১৭: Gift Card মডিউল কীভাবে কাজ করে?

**✅ উত্তর:**  
গিফট কার্ড মডিউলে:
1. **গিফট কার্ড তৈরি** — কার্ড নম্বর, মূল্য, মেয়াদ, গ্রাহক নির্ধারণ করা হয়
2. **টপআপ** — বিদ্যমান গিফট কার্ডে ব্যালেন্স যোগ করা যায়
3. **বিক্রয়ে ব্যবহার** — POS-এ গিফট কার্ড নম্বর স্ক্যান করে পেমেন্ট করা যায়
4. **ব্যালেন্স ট্র্যাকিং** — `giftcards` টেবিলে `value` ও `balance` আলাদা রাখা হয়েছে

---

### ❓ প্রশ্ন ১৮: Accounting বিভাগে কী কী আছে?

**✅ উত্তর:**  
`Accounting` ফোল্ডারে সম্পূর্ণ আর্থিক হিসাব সিস্টেম আছে:

| ফাইল | কার্যক্রম |
|------|----------|
| `cashbook.php` | সমস্ত নগদ লেনদেনের বিবরণী |
| `profit_loss.php` | লাভ-ক্ষতির হিসাব |
| `income_vs_expense.php` | আয় বনাম ব্যয়ের তুলনামূলক চার্ট |
| `income_monthwise.php` | মাসওয়ারি আয়ের বিশ্লেষণ |
| `bank_list.php` | ব্যাংক একাউন্টের তালিকা |
| `bank_transaction_list.php` | ব্যাংক লেনদেনের ইতিহাস |
| `bank_balance_sheet.php` | ব্যাংক ব্যালেন্স শীট |
| `add_income_source.php` | আয়ের উৎস যোগ করা |

---

### ❓ প্রশ্ন ১৯: Reports বিভাগে কতটি রিপোর্ট আছে এবং কী কী?

**✅ উত্তর:**  
`reports` ফোল্ডারে **১৭টি রিপোর্ট** আছে:

1. **Sell Report** — বিক্রয় রিপোর্ট
2. **Purchase Report** — ক্রয় রিপোর্ট
3. **Stock Report** — স্টক রিপোর্ট
4. **Profit & Loss Report** — লাভ-ক্ষতি রিপোর্ট
5. **Balance Sheet** — ব্যালেন্স শীট
6. **Tax Overview Report** — ট্যাক্স সারসংক্ষেপ
7. **Sell Tax Report** — বিক্রয় ট্যাক্স রিপোর্ট
8. **Purchase Tax Report** — ক্রয় ট্যাক্স রিপোর্ট
9. **Income vs Expense Report** — আয়-ব্যয় তুলনা
10. **Cashbook Report** — ক্যাশবুক রিপোর্ট
11. **Bank Transaction Report** — ব্যাংক লেনদেন
12. **Collection Report** — সংগ্রহ রিপোর্ট
13. **Due Collection Report** — বাকি সংগ্রহ রিপোর্ট
14. **Due Paid Report** — বাকি পরিশোধ রিপোর্ট
15. **Sell Payment Report** — বিক্রয় পেমেন্ট
16. **Purchase Payment Report** — ক্রয় পেমেন্ট
17. **Report Overview** — সামগ্রিক আর্থিক সারসংক্ষেপ

---

### ❓ প্রশ্ন ২০: Stock Transfer মডিউল কী?

**✅ উত্তর:**  
একটি মাল্টি-স্টোর সিস্টেমে এক শাখায় স্টক কম হলে অন্য শাখা থেকে স্টক ট্রান্সফার করা যায়। `transfer` ফোল্ডারে এই মডিউল আছে। ট্রান্সফার হলে:
- প্রেরক স্টোরের স্টক কমে
- প্রাপক স্টোরের স্টক বাড়ে
- `transfers` টেবিলে `from_store_id` ও `to_store_id` সহ রেকর্ড থাকে

---

## 📌 বিভাগ ৫: ফ্রন্টএন্ড ও UI প্রশ্ন

---

### ❓ প্রশ্ন ২১: ড্যাশবোর্ডে চার্ট কীভাবে দেখানো হয়?

**✅ উত্তর:**  
ড্যাশবোর্ডে **ApexCharts** লাইব্রেরি ব্যবহার করে গ্রাফ দেখানো হয়। PHP থেকে বিক্রয় এবং ক্রয়ের মাসওয়ারি ডেটা নিয়ে `json_encode()` দিয়ে JavaScript অ্যারেতে পাস করা হয়। তারপর AJAX দিয়ে বছর পরিবর্তন করলে নতুন ডেটা লোড হয়।

```php
$jsIncome  = json_encode($incomeTrend);   // 12 মাসের ডেটা
$jsExpense = json_encode($expenseTrend);
```

চার্টে Income, Expense, Profit — তিনটি ট্যাব সুইচ করা যায়।

---

### ❓ প্রশ্ন ২২: TailwindCSS ব্যবহার করলে কেন?

**✅ উত্তর:**  
TailwindCSS একটি **Utility-First CSS Framework**। এটি ব্যবহার করার কারণ:
1. দ্রুত UI তৈরি করা যায় — আলাদা CSS ফাইলে লিখতে হয় না
2. Responsive Design সহজ — `md:`, `lg:` prefix দিয়ে মোবাইল ও ডেস্কটপ আলাদাভাবে handle করা যায়
3. Modern Look — Glassmorphism, gradient, shadow এফেক্ট সহজে দেওয়া যায়
4. কম CSS লিখতে হয় — ক্লাসগুলো পুনর্ব্যবহারযোগ্য

---

### ❓ প্রশ্ন ২৩: SweetAlert2 কেন ব্যবহার করেছ?

**✅ উত্তর:**  
`SweetAlert2` একটি JavaScript লাইব্রেরি যা ব্রাউজারের দেওয়া সাধারণ `alert()`, `confirm()` বক্সের পরিবর্তে সুন্দর, কাস্টমাইজযোগ্য পপআপ বক্স দেয়। আমি এটি ব্যবহার করেছি:
- ডিলিট কনফার্মেশনের জন্য
- সাফল্য বা ব্যর্থতার বার্তার জন্য
- ফর্ম জমা দেওয়ার পরে নোটিফিকেশনের জন্য

---

### ❓ প্রশ্ন ২৪: DataTables ব্যবহার করেছ কেন?

**✅ উত্তর:**  
`DataTables` একটি jQuery প্লাগইন যা HTML টেবিলকে ইন্টারেক্টিভ করে তোলে। এর সুবিধা:
- **সার্চ ফাংশনালিটি** — তালিকায় তাৎক্ষণিক খোঁজার সুবিধা
- **পেজিনেশন** — অনেক ডেটা পেজে ভাগ করে দেখানো
- **সর্টিং** — যেকোনো কলাম অনুযায়ী সাজানো
- **এক্সপোর্ট** — Excel, PDF, CSV তে রপ্তানি করা যায়

---

## 📌 বিভাগ ৬: চ্যালেঞ্জ ও সমাধান সম্পর্কিত প্রশ্ন

---

### ❓ প্রশ্ন ২৫: প্রজেক্ট তৈরিতে কোন সমস্যার মুখোমুখি হয়েছিলে এবং কীভাবে সমাধান করেছ?

**✅ উত্তর:**  
কয়েকটি উল্লেখযোগ্য চ্যালেঞ্জ:

**১. মাল্টি-স্টোর ফিল্টারিং:**  
সমস্যা — প্রতিটি কুয়েরিতে স্টোর আইডি ফিল্টার করা জটিল ছিল।  
সমাধান — `$storeFilter` ভেরিয়েবল তৈরি করে সব কুয়েরিতে ব্যবহার করা হয়েছে।

**২. .htaccess রিরাইট লুপ:**  
সমস্যা — Clean URL সেটআপ করার সময় সার্ভার 500 এরর দিচ্ছিল।  
সমাধান — `RewriteCond` দিয়ে প্রকৃত ফাইল/ফোল্ডারের জন্য রিরাইট বন্ধ করা হয়েছে।

**৩. ডেটাবেজ মাইগ্রেশন:**  
সমস্যা — নতুন কলাম যোগ করলে পুরানো ইন্সটলেশনে এরর হচ্ছিল।  
সমাধান — `SHOW COLUMNS` দিয়ে কলাম আছে কিনা চেক করে `try-catch` দিয়ে `ALTER TABLE` করা হয়েছে।

**৪. পেমেন্ট মেথড দৃশ্যমানতা:**  
সমস্যা — সব পেমেন্ট মেথড সব স্টোরে দেখাচ্ছিল।  
সমাধান — `payment_store_map` পিভট টেবিল তৈরি করে স্টোরভিত্তিক পেমেন্ট মেথড দেখানো হয়েছে।

---

### ❓ প্রশ্ন ২৬: Quotation ও Sales-এর মধ্যে পার্থক্য কী?

**✅ উত্তর:**  
| বৈশিষ্ট্য | Quotation (কোটেশন) | Sales (বিক্রয়) |
|-----------|---------------------|-----------------|
| উদ্দেশ্য | মূল্য প্রস্তাব দেওয়া | চূড়ান্ত বিক্রয় |
| স্টক প্রভাব | স্টক কমে না | স্টক কমে |
| আর্থিক প্রভাব | হিসাবে আসে না | আয় হিসেবে রেকর্ড হয় |
| রুপান্তর | বিক্রয়ে রূপান্তরিত করা যায় | চূড়ান্ত |

---

### ❓ প্রশ্ন ২৭: Income Sources কীভাবে কাজ করে?

**✅ উত্তর:**  
`income_sources` টেবিলে বিভিন্ন আয়ের উৎস ডিফাইন করা আছে। প্রতিটি উৎসে Feature Flag আছে যেমন:
- `for_sell` — বিক্রয়ের জন্য কিনা
- `for_loan` — লোনের জন্য কিনা
- `for_giftcard_sell` — গিফট কার্ড বিক্রির জন্য কিনা
- `profitable` — লাভজনক কিনা

ডিফল্ট উৎসগুলো হলো: Sell, Purchase Return, Due Collection, Loan Taken, Giftcard Sell, Stock Transfer ইত্যাদি। এটি Cashbook ও Profit/Loss ক্যালকুলেশনে ব্যবহার হয়।

---

## 📌 বিভাগ ৭: ডায়াগ্রাম ও ডকুমেন্টেশন

---

### ❓ প্রশ্ন ২৮: তোমার প্রজেক্টের DFD (Data Flow Diagram) বর্ণনা করো।

**✅ উত্তর:**  
DFD-তে নিচের প্রবাহ দেখানো হয়েছে:

**Level 0 (Context Diagram):**  
- External Entity: Admin, Manager, Cashier, Customer, Supplier
- System: Velocity POS System

**Level 1:**  
- Admin → Store/User Management → Database
- Cashier → POS/Sales → Database → Invoice → Customer
- Manager → Reports → Database
- Supplier → Purchase → Database → Stock

`Diagram/Data-Flow-Diagram.drawio` ফাইলে সম্পূর্ণ DFD সংরক্ষিত আছে।

---

### ❓ প্রশ্ন ২৯: ER Diagram-এ কোন টেবিলগুলোর মধ্যে সম্পর্ক সবচেয়ে জটিল?

**✅ উত্তর:**  
সবচেয়ে জটিল সম্পর্ক হলো **Products ও Stores** — একটি পণ্য অনেক স্টোরে থাকতে পারে এবং একটি স্টোরে অনেক পণ্য থাকতে পারে। এটি **Many-to-Many** সম্পর্ক, যা `product_store_map` পিভট টেবিলের মাধ্যমে সংযুক্ত।

অন্য জটিল সম্পর্ক:
- `selling_info` → `selling_item` (One-to-Many)
- `customers` ↔ `stores` (Many-to-Many via `customer_stores_map`)
- `bank_accounts` ↔ `stores` (Many-to-Many via `bank_account_to_store`)

---

### ❓ প্রশ্ন ৩০: Use Case Diagram-এ কোন কোন ব্যবহারকারী আছেন?

**✅ উত্তর:**  
Use Case Diagram-এ ৩ ধরনের Actor আছেন:

1. **Admin (সুপার অ্যাডমিন)**:
   - সব মডিউলে প্রবেশাধিকার
   - ইউজার ও রোল ম্যানেজমেন্ট
   - স্টোর, ব্যাংক, সিস্টেম সেটআপ

2. **Manager (ম্যানেজার)**:
   - বিক্রয়, ক্রয়, রিপোর্ট দেখা
   - লোন, কিস্তি ব্যবস্থাপনা
   - স্টোর-স্তরের সব কার্যক্রম

3. **Cashier (ক্যাশিয়ার)**:
   - POS স্ক্রিনে বিক্রয়
   - ইনভয়েস প্রিন্ট
   - সীমিত রিপোর্ট দেখার সুবিধা

---

## 📌 বিভাগ ৮: ভবিষ্যৎ পরিকল্পনা ও সাধারণ প্রশ্ন

---

### ❓ প্রশ্ন ৩১: এই প্রজেক্টে কী কী উন্নতি করা যেতে পারে?

**✅ উত্তর:**  
ভবিষ্যতে যা যোগ করা যেতে পারে:

1. **Prepared Statement** — SQL Injection থেকে আরও শক্তিশালী সুরক্ষা
2. **REST API** — মোবাইল অ্যাপের জন্য API তৈরি
3. **PWA (Progressive Web App)** — অফলাইনেও কাজ করার সুবিধা
4. **SMS/Email Notification** — ইনভয়েস ও বাকির নোটিফিকেশন
5. **E-commerce Integration** — অনলাইন শপের সাথে সংযোগ
6. **AI-based Analytics** — বিক্রয় পূর্বাভাস ও পণ্য সুপারিশ
7. **Barcode Scanner App** — মোবাইল দিয়ে বারকোড স্ক্যান
8. **Automated Backup** — ডেটাবেজ স্বয়ংক্রিয় ব্যাকআপ

---

### ❓ প্রশ্ন ৩২: এই ইন্টার্নশিপে তুমি কী শিখলে?

**✅ উত্তর:**  
এই ইন্টার্নশিপে আমি শিখেছি:

1. **ফুল-স্ট্যাক ওয়েব ডেভেলপমেন্ট** — PHP, MySQL, JavaScript একসাথে ব্যবহার
2. **ডেটাবেজ ডিজাইন** — নরমালাইজেশন, Foreign Key, পিভট টেবিল
3. **সফটওয়্যার আর্কিটেকচার** — মডিউলার কোড লেখা, কোড পুনর্ব্যবহার
4. **UI/UX ডিজাইন** — TailwindCSS দিয়ে আধুনিক ইন্টারফেস তৈরি
5. **বাস্তব ব্যবসায়িক সমস্যা সমাধান** — একটি প্রকৃত ব্যবসার প্রয়োজন বুঝে সিস্টেম ডিজাইন করা
6. **ভার্সন কন্ট্রোল** — Git দিয়ে কোড ম্যানেজমেন্ট
7. **সার্ভার কনফিগারেশন** — Apache, .htaccess, MAMP সেটআপ

---

### ❓ প্রশ্ন ৩৩: তোমার প্রজেক্ট কি Live বা Production-এ ডিপ্লয় করা যাবে?

**✅ উত্তর:**  
হ্যাঁ, প্রজেক্টটি যেকোনো Apache+PHP+MySQL সার্ভারে ডিপ্লয় করা যাবে। প্রয়োজনীয়তা:
- PHP 7.4 বা তার উপরে
- MySQL 5.7 বা তার উপরে
- Apache সার্ভার (`mod_rewrite` চালু থাকতে হবে)
- `config/dbcon.php`-এ ডেটাবেজ ক্রেডেনশিয়াল আপডেট করতে হবে

ডিপ্লয়মেন্ট পদক্ষেপ:
1. ফাইল সার্ভারে আপলোড করা
2. ডেটাবেজ তৈরি করা (`pos_system`)
3. `dbcon.php`-এ কানেকশন আপডেট করা
4. প্রথমবার লোড করলে টেবিল স্বয়ংক্রিয়ভাবে তৈরি হবে

---

### ❓ প্রশ্ন ৩৪: তোমার কোড কি রিউজেবল? কীভাবে?

**✅ উত্তর:**  
হ্যাঁ, আমি কোড রিউজেবিলিটির জন্য:

1. **Includes ফোল্ডার** — `header.php`, `sidebar.php`, `navbar.php`, `footer.php` আলাদা রাখা হয়েছে, প্রতিটি পেজে `include()` করা হয়
2. **Permission Helper** — `permission_helper.php` একবার লিখে সব পেজে ব্যবহার
3. **DBcon.php** — একটি ফাইলেই সব টেবিল ক্রিয়েশন ও মাইগ্রেশন
4. **Shared Functions** — `getMonthlyTrend()`, `calculateTrend()` এর মতো কমন ফাংশন

---

### ❓ প্রশ্ন ৩৫: প্রজেক্টের সবচেয়ে জটিল অংশ কোনটি ছিল?

**✅ উত্তর:**  
সবচেয়ে জটিল অংশ ছিল **Accounting Module (Cashbook ও Profit/Loss)**। কারণ:
- বিক্রয়, ক্রয়, ব্যয়, লোন, গিফটকার্ড, স্টক ট্রান্সফার — সব আয়-ব্যয়ের উৎস একটি জায়গায় একত্রিত করতে হয়েছিল
- `income_sources` টেবিলে প্রতিটি উৎসের জন্য Feature Flag দিয়ে ডায়নামিক হিসাব করতে হয়েছে
- মাসওয়ারি, বছরওয়ারি তুলনামূলক বিশ্লেষণের জন্য জটিল SQL কুয়েরি লিখতে হয়েছে
- ব্যাংক ট্র্যান্সফার, ডিপোজিট, উইড্রো আলাদাভাবে ট্র্যাক করতে হয়েছে

---

## 📌 বোনাস: প্যানেল থেকে হঠাৎ করা প্রশ্ন

---

### ❓ প্রশ্ন ৩৬: PHP-তে `include` আর `require`-এর পার্থক্য কী?

**✅ উত্তর:**  
| | `include` | `require` |
|--|-----------|-----------|
| ফাইল না পেলে | Warning দেয়, স্ক্রিপ্ট চলতে থাকে | Fatal Error দেয়, স্ক্রিপ্ট থেমে যায় |
| ব্যবহার | ঐচ্ছিক ফাইলের জন্য | অপরিহার্য ফাইলের জন্য |

আমি `dbcon.php` সংযোজনের জন্য `include()` ব্যবহার করেছি এবং `permission_helper.php`-এর জন্য `include_once()` ব্যবহার করেছি।

---

### ❓ প্রশ্ন ৩৭: GET আর POST-এর পার্থক্য কী? তুমি কোথায় কোনটি ব্যবহার করেছ?

**✅ উত্তর:**  
| | GET | POST |
|--|-----|------|
| ডেটা পাঠানোর জায়গা | URL-এ | HTTP Body-তে |
| নিরাপত্তা | কম (URL দৃশ্যমান) | বেশি |
| ডেটার আকার | সীমিত | বড় ডেটাও পাঠানো যায় |
| ব্যবহার | খোঁজা, ফিল্টার | ফর্ম জমা, ডেটা যোগ/পরিবর্তন |

আমার প্রজেক্টে:
- **GET**: রিপোর্ট ফিল্টার (তারিখ, স্টোর)
- **POST**: পণ্য যোগ, বিক্রয় সম্পন্ন, পেমেন্ট

---

### ❓ প্রশ্ন ৩৮: AJAX কী এবং তুমি কোথায় ব্যবহার করেছ?

**✅ উত্তর:**  
AJAX (Asynchronous JavaScript and XML) হলো পেজ রিলোড না করে সার্ভার থেকে ডেটা নিয়ে আসার পদ্ধতি।

আমার প্রজেক্টে AJAX ব্যবহার করেছি:
- **Dashboard চার্ট** — বছর পরিবর্তন করলে নতুন ডেটা লোড
- **POS পণ্য সার্চ** — টাইপ করলেই পণ্য খোঁজা
- **Select2** — গ্রাহক ও পণ্য লাইভ সার্চ
- **ডিলিট অপারেশন** — কনফার্মের পরে পেজ রিলোড ছাড়াই ডিলিট

---

### ❓ প্রশ্ন ৩৯: SQL-এ JOIN কী? তুমি কোন ধরনের JOIN ব্যবহার করেছ?

**✅ উত্তর:**  
JOIN দুটি বা তার বেশি টেবিলকে একটি শর্তের ভিত্তিতে একত্রিত করে।

আমি মূলত **LEFT JOIN** ব্যবহার করেছি কারণ অনেক ক্ষেত্রে গ্রাহক বা সাপ্লায়ার না থাকলেও বিক্রয়ের তথ্য দেখাতে হয়। উদাহরণ:

```sql
SELECT s.*, c.name as customer_name 
FROM selling_info s 
LEFT JOIN customers c ON s.customer_id = c.id
```

এছাড়া `INNER JOIN` ব্যবহার করেছি যেখানে উভয় টেবিলে ম্যাচিং রো থাকা আবশ্যক।

---

### ❓ প্রশ্ন ৪০: তোমার প্রজেক্টে কোনো Design Pattern আছে?

**✅ উত্তর:**  
হ্যাঁ, যদিও আমি কোনো MVC ফ্রেমওয়ার্ক ব্যবহার করিনি, তবে আমি নিচের প্যাটার্নগুলো অনুসরণ করেছি:

1. **Master-Detail Pattern** — `selling_info` (মাস্টার) ও `selling_item` (ডিটেইল)
2. **Pivot Table Pattern** — Many-to-Many সম্পর্কের জন্য
3. **Feature Flag Pattern** — `income_sources`-এ বিভিন্ন `for_*` কলাম
4. **Include/Reuse Pattern** — `header.php`, `sidebar.php` পুনর্ব্যবহার
5. **Auto-Migration Pattern** — `dbcon.php`-এ স্বয়ংক্রিয় টেবিল তৈরি

---

---

## 📌 বিভাগ ৯: লজিক্যাল প্রশ্ন — ইউজার, স্টোর অ্যাসাইনমেন্ট ও অ্যাক্সেস কন্ট্রোল

---

### ❓ প্রশ্ন ৪১: একজন ইউজার লগইন করলে শুধুমাত্র তার assigned store কীভাবে দেখানো হয়?

**✅ উত্তর:**  
লগইন প্রক্রিয়ায় `config/auth_user.php` ফাইলে `user_store_map` টেবিল থেকে ওই ইউজারের সমস্ত assigned store_id গুলো ফেচ করা হয়:

```php
$store_map_q = mysqli_query($conn, "SELECT store_id FROM user_store_map WHERE user_id = '$u_id'");
$assigned_stores = [];
while ($sm = mysqli_fetch_assoc($store_map_q)) {
    $assigned_stores[] = $sm['store_id'];
}
```

তারপর এই লিস্টটি `$_SESSION['assigned_stores']` এ সেভ হয় এবং প্রথম স্টোরটি `$_SESSION['store_id']` হিসেবে সেট হয়। এই session ভেরিয়েবল ব্যবহার করেই পুরো সিস্টেমে ডেটা ফিল্টার হয়। ইউজার তার assigned store ছাড়া অন্য কোনো store-এর ডেটা দেখতে পারে না।

---

### ❓ প্রশ্ন ৪২: `user_store_map` টেবিলের গঠন কী এবং কেন এটি আলাদা টেবিল?

**✅ উত্তর:**  
`user_store_map` হলো একটি **Pivot (Junction) টেবিল** যা `users` ও `stores` টেবিলের মধ্যে **Many-to-Many** সম্পর্ক তৈরি করে।

```sql
CREATE TABLE IF NOT EXISTS user_store_map (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    store_id INT NOT NULL
);
```

এটি আলাদা টেবিল রাখার কারণ:
- একজন ইউজার একাধিক স্টোরে অ্যাক্সেস পেতে পারে
- একটি স্টোরে একাধিক ইউজার থাকতে পারে
- `users` টেবিলে `store_id` কলাম রাখলে একাধিক স্টোর ম্যানেজ করা কঠিন হতো (ডেটা ডুপ্লিকেশন হতো)

---

### ❓ প্রশ্ন ৪৩: Admin লগইন করলে আর non-admin লগইন করলে store handling-এ কী পার্থক্য?

**✅ উত্তর:**  
`auth_user.php`-এ দুটি আলাদা পথ আছে:

| শর্ত | Admin | Non-Admin |
|------|-------|-----------|
| Store assigned থাকলে | Assigned store গুলোই ব্যবহার হয় | Assigned store গুলোই ব্যবহার হয় |
| **কোনো store assigned না থাকলে** | ডিফল্ট Store ID=1 সেট হয়, লগইন হয় | **লগইন ব্লক** হয়, এরর বার্তা দেখায় |

```php
if (count($assigned_stores) > 0) {
    $_SESSION['store_id'] = $assigned_stores[0];
} else {
    if ($role === 'admin') {
        $_SESSION['store_id'] = 1; // Fallback
    } else {
        // Deny Access
        $_SESSION['message'] = "Access Denied: No stores assigned...";
        header("Location: /pos/login");
        exit(0);
    }
}
```

এই লজিকটি নিরাপত্তার দিক থেকে গুরুত্বপূর্ণ — কোনো staff কে store assign না করলে সে সিস্টেমে ঢুকতেই পারবে না।

---

### ❓ প্রশ্ন ৪৪: একজন ইউজারকে নতুন store কীভাবে assign করা হয়?

**✅ উত্তর:**  
Admin যখন নতুন user তৈরি করে (`users/add_user.php`), সেখানে store selection checkbox থাকে। ফর্ম সাবমিট হলে `users/save_user.php`-এ নিচের কোড চলে:

```php
$store_ids = $_POST['stores'] ?? [];
// User insert করার পর:
$user_id = mysqli_insert_id($conn);
foreach($store_ids as $sid) {
    mysqli_query($conn, "INSERT INTO user_store_map (user_id, store_id) 
                         VALUES ('$user_id', '$sid')");
}
```

**User update করার সময়** (delete + re-insert approach):
```php
// আগের সব mapping delete
mysqli_query($conn, "DELETE FROM user_store_map WHERE user_id='$user_id'");
// নতুন করে insert
foreach($store_ids as $sid) {
    mysqli_query($conn, "INSERT INTO user_store_map (user_id, store_id) 
                         VALUES ('$user_id', '$sid')");
}
```

এই "Delete-then-Reinsert" পদ্ধতিতে সহজেই store permission আপডেট করা যায়।

---

### ❓ প্রশ্ন ৪৫: ইউজার কি নিজে ইচ্ছামতো যেকোনো store-এ switch করতে পারে?

**✅ উত্তর:**  
না, সম্পূর্ণ পারে না। `config/switch_store.php`-এ security check আছে:

```php
$allowed_stores = $_SESSION['assigned_stores'] ?? [];
$is_admin = $_SESSION['auth_user']['role_as'] == 'admin';

if (in_array($new_store_id, $allowed_stores) || $is_admin) {
    $_SESSION['store_id'] = $new_store_id;
} else {
    $_SESSION['message'] = "Access to Store ID $new_store_id Denied";
}
```

অর্থাৎ:
- **Non-admin ইউজার** শুধুমাত্র তার `assigned_stores` লিস্টে থাকা store গুলোতেই switch করতে পারবে
- **Admin** যেকোনো store-এ switch করতে পারবে
- URL manipulation করে অন্য store-এর ID দিলেও server-side চেক তা ব্লক করবে

---

### ❓ প্রশ্ন ৪৬: `$_SESSION['must_select_store']` এর কাজ কী?

**✅ উত্তর:**  
লগইনের সময় যখন `assigned_stores` সেট হয়, তখন `$_SESSION['must_select_store'] = true` সেট করা হয়। এর মানে হলো ইউজার লগইনের পর প্রথমবার ড্যাশবোর্ডে ঢুকলে একটি **Store Selection Modal** পপআপ হয় যেখানে সে কোন store নিয়ে কাজ করবে সেটি বেছে নিতে পারে।

Store switch করার পর `switch_store.php`-এ এই সেশন ভেরিয়েবলটি মুছে দেওয়া হয়:
```php
unset($_SESSION['must_select_store']); // Modal আর দেখাবে না
```

এটি UX-এর দৃষ্টিকোণ থেকে গুরুত্বপূর্ণ — ইউজারকে explicitly তার কাজের store confirm করতে বলা হয়।

---

### ❓ প্রশ্ন ৪৭: কোনো query-তে স্টোর ফিল্টার কীভাবে কাজ করে? উদাহরণ দাও।

**✅ উত্তর:**  
প্রায় প্রতিটি পেজে `$_SESSION['store_id']` বা `$_SESSION['assigned_stores']` ব্যবহার করে কুয়েরি ফিল্টার করা হয়। উদাহরণস্বরূপ Stock Report-এ (Non-Admin):

```php
// Non-admin: শুধু assigned store গুলো দেখাবে
$s_res = mysqli_query($conn, "SELECT s.id, s.store_name 
                              FROM stores s 
                              JOIN user_store_map usm ON s.id = usm.store_id 
                              WHERE usm.user_id = '$uid' AND s.status = 1");
```

Stock Transfer পেজে:
```php
// Transfer লিস্টে শুধু assigned store-এর ট্রান্সফার দেখানো হয়
$query .= " AND (t.from_store_id IN (SELECT store_id FROM user_store_map WHERE user_id = '$user_id') 
             OR t.to_store_id IN (SELECT store_id FROM user_store_map WHERE user_id = '$user_id'))";
```

এভাবে ডেটা এমনিতেই আলাদা থাকে — ইউজার শুধু তার store-এর ডেটাই দেখতে পায়।

---

### ❓ প্রশ্ন ৪৮: যদি কোনো ইউজারের store delete করা হয়, তার access কী হবে?

**✅ উত্তর:**  
যদি কোনো store delete হয়, তাহলে `user_store_map` টেবিলে ওই store-এর mapping ও delete হবে (Foreign Key `ON DELETE CASCADE` থাকলে)। পরবর্তী লগইনের সময় `auth_user.php`-এ `user_store_map` থেকে ফেচ করলে ওই store_id আর আসবে না।

যদি ইউজারের সব store চলে যায় এবং সে Admin না হয়, তাহলে:
```php
// assigned_stores হবে খালি array
// Non-admin হলে login block হবে:
$_SESSION['message'] = "Access Denied: No stores assigned to your account.";
header("Location: /pos/login");
exit(0);
```

অর্থাৎ store delete হলে সংশ্লিষ্ট Non-admin ইউজার স্বয়ংক্রিয়ভাবে সিস্টেম থেকে লকড হয়ে যায়।

---

### ❓ প্রশ্ন ৪৯: Payment Method কি সব store-এ একই? নাকি store-ভিত্তিক আলাদা?

**✅ উত্তর:**  
Store-ভিত্তিক আলাদা। `payment_store_map` পিভট টেবিলে প্রতিটি store-এর জন্য allowed payment method গুলো ম্যাপ করা আছে। POS Checkout Modal-এ শুধুমাত্র current store-এর assigned payment method গুলো দেখা যায়।

```php
// payment_modal.php-এ:
$assigned_stores = $pm_map[$pm['id']] ?? '';
// data-stores attribute দিয়ে JS-এ ফিল্টার করা হয়
```

এটি একটি বাস্তব ব্যবসায়িক প্রয়োজন — উদাহরণস্বরূপ, একটি শাখায় Bkash থাকলেও অন্য শাখায় না-ও থাকতে পারে।

---

### ❓ প্রশ্ন ৫০: Sidebar-এ কীভাবে বর্তমান user-এর store নাম দেখানো হয় এবং একাধিক store থাকলে কী হয়?

**✅ উত্তর:**  
`includes/navbar.php`-এ `$_SESSION['store_id']` দিয়ে বর্তমান active store-এর নাম ডেটাবেজ থেকে ফেচ করা হয় এবং navbar-এ দেখানো হয়।

একাধিক store থাকলে navbar-এ একটি **Store Switcher Dropdown** দেখানো হয়:

```php
$store_count_q = mysqli_query($conn, 
    "SELECT COUNT(*) as cnt FROM user_store_map WHERE user_id='$u_id'"
);
// যদি count > 1 হয়, তাহলে dropdown দেখাও
```

ইউজার dropdown থেকে যেকোনো assigned store বেছে নিতে পারে, যা `switch_store.php`-এ POST হয়ে `$_SESSION['store_id']` আপডেট করে। এরপর সমস্ত কুয়েরি নতুন store-এর ডেটা দেখাবে।

---

## 📌 বিভাগ ১০: চ্যালেঞ্জিং প্রশ্ন — PHP SESSION গভীরে

---

### ❓ প্রশ্ন ৫১: PHP Session আসলে কী? এটা কীভাবে কাজ করে?

**✅ উত্তর:**  
Session হলো সার্ভারের পক্ষ থেকে একটি নির্দিষ্ট ইউজারের জন্য তথ্য সাময়িকভাবে সংরক্ষণের পদ্ধতি। ব্রাউজার স্বভাবতই **Stateless** — মানে প্রতিটি HTTP request স্বাধীন, আগের request-এর কথা মনে থাকে না। Session এই সমস্যার সমাধান করে।

**কীভাবে কাজ করে:**
1. `session_start()` কল হলে PHP একটি unique **Session ID** (PHPSESSID) তৈরি করে
2. এই ID টি ব্রাউজারে **Cookie** হিসেবে পাঠানো হয়
3. Session ডেটা সার্ভারের `/tmp` ফোল্ডারে `sess_[id]` ফাইলে সংরক্ষিত হয়
4. পরবর্তী request-এ ব্রাউজার সেই Cookie পাঠায়, PHP ম্যাচ করে সেই ফাইল লোড করে

```
Browser                     Server
   |--- GET /dashboard ------->|
   |<-- Set-Cookie: PHPSESSID=abc123 --|
   |                           | [sess_abc123 ফাইলে ডেটা]
   |--- GET /products (Cookie: PHPSESSID=abc123) -->|
   |                           | [ফাইল পড়ে ইউজার চিনল]
```

---

### ❓ প্রশ্ন ৫২: তোমার প্রজেক্টে Session কোথায় কোথায় কী কাজে লাগানো হয়েছে?

**✅ উত্তর:**  
আমার প্রজেক্টে Session তিনটি প্রধান কাজে লাগানো হয়েছে:

| Session Variable | কাজ |
|-----------------|-----|
| `$_SESSION['auth']` | ইউজার লগড-ইন কিনা তা চেক করে |
| `$_SESSION['auth_user']` | ইউজারের নাম, role, permissions, image সংরক্ষণ |
| `$_SESSION['store_id']` | বর্তমান active store কোনটি |
| `$_SESSION['assigned_stores']` | ইউজার কোন কোন store দেখতে পারবে |
| `$_SESSION['must_select_store']` | লগইনের পর store selection modal দেখাবে কিনা |
| `$_SESSION['message']` | Flash message (success/error notification) |
| `$_SESSION['msg_type']` | Flash message-এর ধরন (success/error/warning) |

প্রতিটি পেজে `header.php`-এ প্রথমেই চেক:
```php
if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}
```

---

### ❓ প্রশ্ন ৫৩: Session ব্যবহারের সুবিধাগুলো কী কী?

**✅ উত্তর:**  

1. **সার্ভার-সাইড স্টোরেজ** — ডেটা ক্লায়েন্টে থাকে না, তাই সহজে পরিবর্তন বা চুরি করা যায় না
2. **যেকোনো ডেটা টাইপ** — Array, Object সহ যেকোনো PHP variable সংরক্ষণ করা যায় (Cookie শুধু string পারে)
3. **স্বয়ংক্রিয় পরিচয়** — একবার লগইন করলে সব পেজে `$_SESSION['auth_user']` দিয়ে ইউজারের তথ্য পাওয়া যায়
4. **Flash Message** — একটি পেজ থেকে অন্য পেজে redirect করার সময় message পাঠানো সহজ
5. **Permission Cache** — ডেটাবেজে বারবার permission কুয়েরি না করে session থেকে দ্রুত পাওয়া যায়

---

### ❓ প্রশ্ন ৫৪: Session ব্যবহারে কী কী সমস্যা বা ঝুঁকি আছে?

**✅ উত্তর:**  
কয়েকটি গুরুত্বপূর্ণ দুর্বলতা:

**১. Session Hijacking (চুরি)**  
যদি কেউ PHPSESSID Cookie চুরি করতে পারে, সে ওই ইউজার হিসেবে সব কাজ করতে পারবে।  
*সমাধান:* HTTPS ব্যবহার করা, `session.cookie_httponly = true` সেট করা।

**২. Session Fixation**  
আক্রমণকারী আগে থেকে একটি Session ID তৈরি করে ইউজারকে সেটি দিয়ে লগইন করায়, তারপর সেই ID দিয়ে অ্যাক্সেস নেয়।  
*সমাধান:* লগইন সফল হলে `session_regenerate_id(true)` কল করা।

**৩. সার্ভারের Disk ব্যবহার**  
অনেক ইউজার হলে সার্ভারে অনেক session ফাইল তৈরি হয়, যা Disk শেষ করে দিতে পারে।  
*সমাধান:* Session Garbage Collection বা Database-based session storage।

**৪. Load Balancer সমস্যা**  
একাধিক সার্ভার থাকলে session ফাইল শুধু একটি সার্ভারে থাকে, ইউজার অন্য সার্ভারে গেলে session হারায়।  
*সমাধান:* Redis বা Database-based session।

**৫. Session Timeout নেই**  
আমার বর্তমান প্রজেক্টে explicit session timeout নেই — অর্থাৎ ব্রাউজার বন্ধ না করলে session থাকে।

---

### ❓ প্রশ্ন ৫৫: Session আর Cookie-র মধ্যে পার্থক্য কী?

**✅ উত্তর:**  

| বৈশিষ্ট্য | Session | Cookie |
|-----------|---------|--------|
| ডেটা কোথায় থাকে | **সার্ভারে** (tmp ফাইল) | **ব্রাউজারে** |
| নিরাপত্তা | বেশি নিরাপদ | কম নিরাপদ (ইউজার পরিবর্তন করতে পারে) |
| ডেটার আকার | সীমা নেই (practically) | সর্বোচ্চ ~4KB |
| ডেটার ধরন | Array, Object সহ যেকোনো | শুধু String |
| মেয়াদ | ব্রাউজার বন্ধ হলে শেষ (default) | নির্দিষ্ট তারিখ পর্যন্ত থাকে |
| ব্যবহার | Authentication, Permissions | Remember Me, Language Preference |

আমার প্রজেক্টে authentication-এর জন্য Session ব্যবহার করেছি কারণ এটি বেশি নিরাপদ।

---

### ❓ প্রশ্ন ৫৬: `session_start()` না করলে কী হবে?

**✅ উত্তর:**  
`session_start()` না করলে `$_SESSION` ভেরিয়েবল কাজ করবে না। PHP এই error দেবে:

```
Notice: Undefined variable: _SESSION
```

এবং `$_SESSION['auth']` চেক করলে সবসময় `false` হবে, ফলে প্রতিটি পেজে লগইন পেজে redirect হবে।

আমার প্রজেক্টে প্রতিটি পেজে `header.php`-এর শুরুতে প্রথমেই `session_start()` কল হয়। তাই এই সমস্যা হয় না।

> ⚠️ **গুরুত্বপূর্ণ:** `session_start()` অবশ্যই যেকোনো HTML output-এর **আগে** কল করতে হবে, নইলে `"headers already sent"` error আসে।

---

### ❓ প্রশ্ন ৫৭: ইউজার লগআউট হলে Session কীভাবে ধ্বংস করা হয়?

**✅ উত্তর:**  
লগআউটের সময় Session সম্পূর্ণভাবে নষ্ট করতে তিনটি ধাপ দরকার:

```php
// ১. সব session variable মুছে দাও
$_SESSION = [];

// ২. Session Cookie মুছে দাও
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// ৩. সার্ভার থেকে Session ফাইল ডিলিট করো
session_destroy();

header("Location: /pos/login");
exit(0);
```

শুধু `session_destroy()` করলেই যথেষ্ট নয় — পুরানো PHPSESSID Cookie ব্রাউজারে থেকে যায়। তাই PHP Manual অনুযায়ী উপরের তিনটি ধাপই দরকার।

---

### ❓ প্রশ্ন ৫৮: তোমার প্রজেক্টে Session-এর কোনো দুর্বলতা কি আছে? থাকলে কীভাবে ঠিক করবে?

**✅ উত্তর:**  
হ্যাঁ, আমার বর্তমান implementation-এ কয়েকটি দুর্বলতা আছে:

**দুর্বলতা ১ — `session_regenerate_id()` নেই:**  
লগইন সফল হলে নতুন Session ID তৈরি করিনি। এটি Session Fixation Attack-এর ঝুঁকি তৈরি করে।  
*উন্নতি:*
```php
// লগইন সফল হওয়ার পরে
session_regenerate_id(true);
$_SESSION['auth'] = true;
```

**দুর্বলতা ২ — Session Timeout নেই:**  
ইউজার লগইন করলে ব্রাউজার না বন্ধ করলে session চিরকাল থাকে।  
*উন্নতি:*
```php
// শেষ activity-র সময় সংরক্ষণ
$_SESSION['last_activity'] = time();
// প্রতি পেজে চেক:
if (time() - $_SESSION['last_activity'] > 1800) { // ৩০ মিনিট
    session_destroy();
    header("Location: /pos/login");
}
```

**দুর্বলতা ৩ — HTTP (non-HTTPS):**  
Local development-এ HTTPS নেই, তাই Session Cookie plain text-এ যায় (network-এ sniff করা যেতে পারে)।

---

### ❓ প্রশ্ন ৫৯: Flash Message মানে কী? তোমার প্রজেক্টে কীভাবে ব্যবহার করেছ?

**✅ উত্তর:**  
**Flash Message** হলো এমন একটি বার্তা যা একবার দেখানোর পর স্বয়ংক্রিয়ভাবে মুছে যায়। একটি action সম্পন্ন হওয়ার পর redirect করার সময় ইউজারকে জানানো হয় — "সফল হয়েছে" বা "ত্রুটি হয়েছে"।

আমার প্রজেক্টে এটি Session দিয়ে করা হয়েছে:

```php
// save_user.php — user সেভ করার পর:
$_SESSION['message'] = "User Created Successfully!";
$_SESSION['msg_type'] = "success";
header("Location: /pos/users/list");
exit(0);
```

```php
// header.php — পরের পেজে বার্তা দেখানো ও মুছে দেওয়া:
if(isset($_SESSION['message'])) {
    // SweetAlert2 দিয়ে সুন্দর popup দেখাও
    echo "<script>Swal.fire('{$_SESSION['message']}');</script>";
    unset($_SESSION['message']); // একবার দেখানোর পর মুছে দাও
    unset($_SESSION['msg_type']);
}
```

এটি **PRG (Post-Redirect-Get)** Pattern-এর অংশ — form submit → process → redirect → show message।

---

### ❓ প্রশ্ন ৬০: `$_SESSION['auth_user']` Array-এ কী কী তথ্য থাকে এবং কেন এই তথ্যগুলো session-এ রাখা হলো?

**✅ উত্তর:**  
লগইনের সময় `auth_user.php`-এ Session-এ এই array সেট হয়:

```php
$_SESSION['auth_user'] = [
    'user_id'     => $data['id'],        // ইউজারের DB ID
    'name'        => $data['name'],       // প্রদর্শনের জন্য নাম
    'email'       => $data['email'],      // প্রোফাইল পেজে দেখানো
    'role_as'     => $role,              // admin/cashier/manager ইত্যাদি
    'image'       => $data['user_image'],// Navbar-এ avatar দেখানো
    'permissions' => $user_permissions   // কোন feature access পাবে
];
```

**কেন session-এ রাখা হলো:**
- প্রতিটি পেজে DB-তে কুয়েরি না করে তাৎক্ষণিক ইউজার তথ্য পাওয়া যায়
- Navbar-এ নাম ও ছবি দেখানো সহজ হয়
- `role_as` দিয়ে Admin/Non-admin সিদ্ধান্ত নেওয়া যায়
- `permissions` array দিয়ে প্রতিটি feature-এর access check করা যায় — ডেটাবেজে বারবার কুয়েরি না করে

> ⚠️ **সতর্কতা:** Session-এ সংবেদনশীল তথ্য যেমন plain text password কখনো রাখা উচিত নয়।

---

> 📝 **নোট:** এই ডকুমেন্টটি **Velocity POS** প্রজেক্টের ইন্টার্নশিপ ডিফেন্সের জন্য প্রস্তুত করা হয়েছে। সমস্ত প্রশ্নের উত্তর প্রজেক্টের প্রকৃত কোড বিশ্লেষণের ভিত্তিতে দেওয়া হয়েছে।
>
> **প্রজেক্ট পাথ:** `/Applications/MAMP/htdocs/pos/`  
> **ডেভেলপার:** শরীফউল্লাহ ঢালী  
> **তারিখ:** ফেব্রুয়ারি ২০২৬




# 🎓 ইন্টার্নশিপ ডিফেন্স — প্রশ্ন ও উত্তর
### প্রজেক্ট: **Krelyon — Software Development Agency Website (v3)**
> **ভাষা:** HTML5, Vanilla CSS, Vanilla JavaScript, GSAP 3, PHP  
> **Animation:** GSAP + ScrollTrigger + Pure CSS Animations  
> **ডেভেলপার:** শরীফউল্লাহ ঢালী  

---

## 📌 বিভাগ ১: প্রজেক্ট পরিচিতি ও সাধারণ প্রশ্ন

---

### ❓ প্রশ্ন ১: তোমার প্রজেক্টের নাম কী এবং এটি কী ধরনের?

**✅ উত্তর:**  
প্রজেক্টের নাম **Krelyon** — এটি একটি **Software Development Agency-র Corporate Website**। এই সাইটে কোম্পানির সার্ভিস, পোর্টফোলিও, প্রোডাক্ট, মাইলস্টোন, ক্যারিয়ার পেজ ও Contact Form আছে। এটি মূলত Krelyon-এর digital presence এবং ক্লায়েন্ট acquisition-এর জন্য তৈরি।

---

### ❓ প্রশ্ন ২: এই প্রজেক্টে কোন কোন প্রযুক্তি ব্যবহার করেছ?

**✅ উত্তর:**

| স্তর | প্রযুক্তি | কেন ব্যবহার |
|------|-----------|------------|
| **Structure** | HTML5 (Semantic) | পেজের কাঠামো তৈরি |
| **Styling** | Vanilla CSS + CSS Variables | নিজস্ব Design System, Dark Mode |
| **Animation** | **GSAP 3** (GreenSock) | Professional scroll animation |
| **Scroll Trigger** | **GSAP ScrollTrigger** | স্ক্রলে animation চালু হয় |
| **JavaScript** | Vanilla JS (ES6+) | DOM manipulation, interactive UI |
| **Font** | **Sora + Space Grotesk** (Google Fonts) | Modern typography |
| **Icon** | Inline SVG | Fast, scalable, CSS-controlled |
| **Backend** | PHP (`contact.php`, `apply.php`) | Contact ও Job Apply form |
| **Server** | Apache + MAMP | Local development |
| **URL** | `.htaccess` mod_rewrite | Clean URL (/about/, /service) |

---

### ❓ প্রশ্ন ৩: এই প্রজেক্টে কতটি পেজ আছে?

**✅ উত্তর:**  
প্রজেক্টে **৭+ পেজ** আছে:

| পেজ / ফোল্ডার | উদ্দেশ্য |
|--------------|---------|
| `index.html` | **হোম পেজ** — Hero, Projects, Services, Stats, Process, Products, FAQ |
| `about/` | কোম্পানি পরিচিতি |
| `service/` | সার্ভিস পেজ (Mobile App, Web Dev, AI/ML, DevOps, Cloud ইত্যাদি) |
| `products/` | প্রোডাক্ট পোর্টফোলিও |
| `milestone/` | কোম্পানির মাইলস্টোন |
| `careers/` | চাকরির বিজ্ঞপ্তি |
| `job-frontend-developer/`, `job-pm/`, `job-ui-ux/` | নির্দিষ্ট Job বিজ্ঞপ্তি |
| `apply.php` | Job Application Form (CV upload সহ) |
| `contact.php` | Contact Form Backend |

---

### ❓ প্রশ্ন ৪: কোনো Framework ব্যবহার করেছ? React/Vue কেন নেই?

**✅ উত্তর:**  
না, আমি **Pure HTML + CSS + Vanilla JS** ব্যবহার করেছি — কোনো React/Vue নেই। কারণ:
1. Corporate Website-এর জন্য ভারী JS Framework দরকার নেই
2. Page load অনেক দ্রুত হয় (no virtual DOM overhead)
3. GSAP দিয়েই সব animation সম্ভব
4. SEO-friendly — Server-side rendered static HTML

---

### ❓ প্রশ্ন ৫: এই প্রজেক্ট কেন তৈরি করলে?

**✅ উত্তর:**  
**Krelyon Software Agency** একটি Real Company যারা Mobile App, Web Development, UI/UX, AI/ML, DevOps সার্ভিস দেয়। তাদের digital presence এবং client acquisition-এর জন্য একটি **Professional, Animated, Mobile-Responsive Corporate Website** দরকার ছিল।

---

## 📌 বিভাগ ২: Animation সম্পর্কিত প্রশ্ন (সবচেয়ে গুরুত্বপূর্ণ)

---

### ❓ প্রশ্ন ৬: GSAP কী? কেন ব্যবহার করলে?

**✅ উত্তর:**  
GSAP (GreenSock Animation Platform) একটি Professional JavaScript Animation Library। আমি ব্যবহার করেছি কারণ:

| বিষয় | CSS Animation | GSAP |
|-------|--------------|------|
| Timeline control | নেই | আছে (`.timeline()`) |
| ScrollTrigger | নেই | আছে (plugin) |
| JS দিয়ে control | সীমিত | সম্পূর্ণ |
| Easing options | সীমিত | ১০০+ |
| Performance | ভালো | GPU-optimized |

---

### ❓ প্রশ্ন ৭: প্রজেক্টে কী কী Animation আছে? বিস্তারিত বলো।

**✅ উত্তর:**  
প্রজেক্টে মোট **১৩টি Animation** আছে:

#### ১. 🎬 Preloader Animation
পেজ লোডের আগে কালো পর্দা। মাঝে একটি সবুজ বৃত্ত `scale(0) → scale(50)` হয়ে পর্দা ঢাকে, তারপর fade হয়ে মূল পেজ দেখায়।
```javascript
const tl = gsap.timeline();
tl.to(preloaderCircle, { scale: 50, duration: 1.5, ease: "power3.inOut" })
  .to(preloaderBg, { opacity: 0, duration: 1 }, "-=1.5")
  .to(header, { opacity: 1, duration: 1 }, "-=0.5");
```

#### ২. 📝 Hero Text Slide-Up
"TRANSFORMING IDEAS" ও "INTO SOFTWARE" লেখা নিচ থেকে উঠে আসে।
```css
.anim-line-inner { transform: translateY(110%); } /* parent overflow:hidden */
```
```javascript
tl.to(".hero .anim-line-inner", { y: 0, stagger: 0.15, ease: "power3.out" });
```

#### ③ 🎞️ Hero Grid (ভাসন্ত Technology Logo)
ব্যাকগ্রাউন্ডে টেকনোলজি লোগো তিনটি কলামে তিনটি গতিতে উপরে-নিচে চলে।
```css
.col-content { animation: hero-scroll 30s linear infinite; }
.col-2 .col-content { animation-duration: 40s; animation-direction: reverse; }
@keyframes hero-scroll { 100% { transform: translateY(-50%); } }
```

#### ④ ⏩ Technology Scroller
হিরোর নিচে "Next.js, ReactJs, SpringBoot..." ডান থেকে বামে অসীমভাবে চলে।
```css
animation: hero-client-scroll 40s linear infinite;
@keyframes hero-client-scroll { 100% { transform: translateX(-100%); } }
```

#### ⑤ 🔢 Stats Counter
"160+", "40+" ইত্যাদি সংখ্যা স্ক্রল করলে ০ থেকে count করে।
```javascript
gsap.to(counter, { val: endValue, duration: 2,
  scrollTrigger: { trigger: item, start: "top 90%" },
  onUpdate: () => { stat.textContent = Math.ceil(counter.val) + suffix; }
});
```

#### ⑥ 🎪 Marquee Banner
"WEB DESIGN ★ DEVELOPMENT ★ UI/UX" চলমান ব্যানার — দুটি কপি `translateX(-50%)` infinite।
```css
.marquee-track { animation: marquee-scroll 25s linear infinite; }
@keyframes marquee-scroll { 100% { transform: translateX(-50%); } }
```

#### ⑦ 📜 Scroll-Trigger Stagger Cards
Project/Service/Tech Logo card গুলো স্ক্রলে একে একে `opacity: 0 → 1` হয়।
```javascript
gsap.to(".project-card", {
  opacity: 1, y: 0, stagger: 0.15,
  scrollTrigger: { trigger: ".project-grid", start: "top 85%" }
});
```

#### ⑧ 🖼️ Product Hover Preview
Product নামে hover করলে ডান দিকে ছবি বদলায়।
```javascript
item.addEventListener("mouseenter", () => targetImage.classList.add("visible"));
```

#### ⑨ 🔄 Process Tabs
"Discover → Define → Develop → Evolve" ক্লিক করলে panel বদলায়।

#### ⑩ 📱 Mobile Hamburger → X
তিনটি line ক্লিকে X হয়; full-screen nav `translateY(20px→0)` আসে।

#### ⑪ 🎥 Video Modal
ভিডিও বাটনে ক্লিক করলে `opacity 0→1` transition-এ modal আসে, ভিডিও autoplay হয়।

#### ⑫ 🖱️ Scrolled Navbar
৫০px স্ক্রলে navbar-এ `backdrop-filter: blur(10px)` Glassmorphism effect।

#### ⑬ 🌈 Body Gradient
`background: linear-gradient(45deg, #111827, #1f2937)` CSS animation দিয়ে ধীরে ধীরে পরিবর্তন।

---

### ❓ প্রশ্ন ৮: Stagger মানে কী?

**✅ উত্তর:**  
Stagger মানে প্রতিটি element-এ একটু করে delay যোগ করা। উদাহরণ: `stagger: 0.15` মানে প্রথম card ০ sec-এ, দ্বিতীয় ০.১৫ sec-এ, তৃতীয় ০.৩ sec-এ আসে। এতে animation organic, wave-like দেখায়।

---

### ❓ প্রশ্ন ৯: ScrollTrigger কীভাবে কাজ করে?

**✅ উত্তর:**  
ScrollTrigger GSAP-এর একটি Plugin। এটি window-এর scroll position দেখে নির্দিষ্ট element screen-এর কতটুকু ভেতরে এলে animation শুরু হবে সেটি নির্ধারণ করে।
```javascript
scrollTrigger: {
    trigger: ".project-grid",  // কোন element দেখলে
    start: "top 85%",          // element-এর top, viewport-এর 85%-এ এলে
}
```

---

### ❓ প্রশ্ন ১০: GSAP load না হলে কী হবে?

**✅ উত্তর:**  
`js/home.js`-এ Fallback Logic আছে:
```javascript
if (typeof gsap === "undefined" || typeof ScrollTrigger === "undefined") {
    // সব element manually visible করো
    document.querySelectorAll(".anim-fade-in, .stat-item, .project-card")
        .forEach(item => { item.style.opacity = "1"; item.style.transform = "none"; });
    // Tab, Menu, Accordion তবুও চলবে
    initializeTabLogic();
    initializeMobileMenu();
    return;
}
```
এই **Graceful Degradation** নিশ্চিত করে যে GSAP CDN fail হলেও site ব্যবহারযোগ্য থাকে।

---

## 📌 বিভাগ ৩: CSS ও Design সম্পর্কিত প্রশ্ন

---

### ❓ প্রশ্ন ১১: CSS Variable কী এবং কেন ব্যবহার করলে?

**✅ উত্তর:**  
CSS Custom Properties (`:root`-এ defined) দিয়ে সারা siteএর color, font একটি জায়গা থেকে control করা যায়।
```css
:root {
    --bg-color: #111827;         /* Dark background */
    --accent-color: #34d399;     /* Electric Green */
    --text-color: #e5e7eb;       /* Soft White */
    --text-muted: #9ca3af;       /* Gray */
}
/* এখন যেকোনো জায়গায়: */
button { background-color: var(--accent-color); }
```
**সুবিধা:** একটি জায়গায় রঙ বদলালে পুরো site বদলে যায়।

---

### ❓ প্রশ্ন ১২: Dark Theme কীভাবে করলে?

**✅ উত্তর:**  
`:root`-এ dark color define করে সব element সেই variable ব্যবহার করে। `body`-এর `background-color: var(--bg-color)` এবং `color: var(--text-color)` দিয়ে পুরো Dark Mode তৈরি।

---

### ❓ প্রশ্ন ১৩: Glassmorphism Navbar কীভাবে করলে?

**✅ উত্তর:**  
```css
header.scrolled-nav {
    background-color: rgba(17, 24, 39, 0.75); /* Semi-transparent */
    backdrop-filter: blur(10px);              /* পেছনে blur */
    border-bottom: 1px solid #374151;
}
```
`backdrop-filter: blur()` element-এর পেছনের content blur করে — এটি Glassmorphism effect।

---

### ❓ প্রশ্ন ১৪: দুটি Font কেন ব্যবহার করলে?

**✅ উত্তর:**  
| Font | ব্যবহার | কেন |
|------|---------|-----|
| **Sora** | Body text, nav | Readable, modern, clean |
| **Space Grotesk** | H1, H2, Buttons, Logo | Bold, industrial, agency-feel |

দুটি আলাদা font দিয়ে Visual Hierarchy তৈরি হয় — headings আলাদাভাবে চোখে পড়ে।

---

### ❓ প্রশ্ন ১৫: `overflow: hidden` h1-এ কেন দেওয়া হয়েছে?

**✅ উত্তর:**  
Hero text animation-এর কৌশল:
1. `.anim-line-inner` শুরুতে `translateY(110%)` — parent div-এর নিচে লুকানো
2. parent-এ `overflow: hidden` — লুকানো অংশ দেখা যায় না
3. GSAP `y: 0` করলে text নিচ থেকে উপরে উঠে আসে

এটি classic "Text Reveal" এফেক্ট।

---

## 📌 বিভাগ ৪: JavaScript ও ইন্টারেক্টিভিটি

---

### ❓ প্রশ্ন ১৬: Hamburger Menu কীভাবে কাজ করে?

**✅ উত্তর:**  
```javascript
// Toggle button click করলে:
document.body.classList.toggle("nav-open-no-scroll");
document.getElementById("mobile-nav").classList.toggle("open");
```
```css
body.nav-open-no-scroll .line1 { transform: translateY(9px) rotate(45deg); }
body.nav-open-no-scroll .line2 { opacity: 0; }
body.nav-open-no-scroll .line3 { transform: translateY(-9px) rotate(-45deg); }
```
তিনটি bar: প্রথম ও তৃতীয় rotate হয়ে X তৈরি করে, মেঝেরটা fade হয়।

---

### ❓ প্রশ্ন ১৭: Product Hover কীভাবে কাজ করে?

**✅ উত্তর:**  
প্রতিটি product item-এ `data-image` attribute দেওয়া:
```html
<a class="product-item" data-image="/images/restaurant.png">Restaurant System</a>
```
```javascript
item.addEventListener("mouseenter", () => {
    const targetImage = document.querySelector(`img[data-id="${targetImageId}"]`);
    previewImages.forEach(img => img.classList.remove("visible"));
    targetImage.classList.add("visible"); // CSS opacity transition দিয়ে দেখায়
});
```

---

### ❓ প্রশ্ন ১৮: Stats Counter কীভাবে data পায়?

**✅ উত্তর:**  
HTML-এ `data-count` attribute থেকে:
```html
<h3 data-count="160">0+</h3>
```
```javascript
const endValue = stat.getAttribute("data-count"); // "160"
const suffix = stat.innerText.replace(/[0-9]/g, ""); // "+"
gsap.to(counter, { val: endValue, onUpdate: () => stat.textContent = Math.ceil(counter.val) + suffix });
```

---

### ❓ প্রশ্ন ১৯: Contact Form কীভাবে কাজ করে?

**✅ উত্তর:**  
HTML Form → `contact.php` (PHP Mail function)। User তথ্য দিলে PHP `mail()` function দিয়ে email পাঠায়।

Job Application Form (`apply.php`)-এ CV upload ফিচার আছে — `$_FILES` দিয়ে file handle করা হয়।

---

## 📌 বিভাগ ৫: সাধারণ Web প্রশ্ন

---

### ❓ প্রশ্ন ২০: Inline SVG vs Icon Font (Font Awesome) — কোনটি ভালো?

**✅ উত্তর:**  
| বিষয় | Inline SVG | Font Awesome |
|-------|------------|--------------|
| HTTP Request | নেই | আলাদা CSS file লোড |
| CSS Control | সরাসরি stroke/fill | শুধু color/size |
| Accessibility | `aria-label` সহজে | সীমিত |
| Performance | দ্রুত | তুলনামূলক ধীর |

আমি Inline SVG ব্যবহার করেছি কারণ বেশি fast ও flexible।

---

### ❓ প্রশ্ন ২১: `position: fixed` কী করে?

**✅ উত্তর:**  
Navbar-এ `position: fixed; top: 0; width: 100%` দেওয়া হয়েছে। এতে user scroll করলেও navbar সবসময় উপরে দেখা যায়।

---

### ❓ প্রশ্ন ২২: `z-index` কেন দরকার?

**✅ উত্তর:**  
```css
#preloader-bg { z-index: 1001; }    /* সবার উপরে */
#preloader-circle { z-index: 1002; } /* আরও উপরে */
#mobile-nav { z-index: 1100; }       /* Mobile nav */
header { z-index: 100; }             /* Header */
```
`z-index` নির্ধারণ করে কোন element কোনটির উপরে থাকবে।

---

### ❓ প্রশ্ন ২৩: `backdrop-filter` কী?

**✅ উত্তর:**  
`backdrop-filter: blur(10px)` element-এর পেছনের সবকিছু blur করে। এটি **Frosted Glass / Glassmorphism** effect তৈরি করে। শুধু modern browser-এ কাজ করে।

---

### ❓ প্রশ্ন ২৪: Semantic HTML কী? কেন ব্যবহার করলে?

**✅ উত্তর:**  
অর্থবহ HTML tag যা content-এর কাজ বোঝায়:
```html
<header> — পেজের শীর্ষাংশ
<nav>    — Navigation
<main>   — মূল content
<section> — একটি বিভাগ
<footer> — পাদচরণ
```
**সুবিধা:** SEO ভালো হয়, Screen Reader সহজে পড়তে পারে, কোড বোঝা সহজ।

---

### ❓ প্রশ্ন ২৫: `requestAnimationFrame` vs `setInterval`?

**✅ উত্তর:**  
GSAP internally `requestAnimationFrame` ব্যবহার করে — এটি browser-এর refresh rate (60fps) এর সাথে sync থাকে। `setInterval` fixed time-এ চলে, frame drop হতে পারে। তাই GSAP animation অনেক smooth।

---

## 📌 বিভাগ ৬: চ্যালেঞ্জ ও ভবিষ্যৎ

---

### ❓ প্রশ্ন ২৬: সবচেয়ে কঠিন অংশ কোনটি ছিল?

**✅ উত্তর:**  
সবচেয়ে কঠিন ছিল **GSAP Preloader Timeline**:
- Timeline-এর প্রতিটি `.to()` call সুনির্দিষ্ট offset (যেমন `"-=1.5"`) দিয়ে overlapping করা
- Preloader শেষ হলে Hero animations শুরু হওয়ার নিখুঁত timing ঠিক করা
- Content `visibility: hidden` → `visible` সঠিক সময়ে করা

এবং **Seamless Marquee** — দুটি কপি ব্যবহার করে `translateX(-50%)` দিয়ে perfect loop।

---

### ❓ প্রশ্ন ২৭: ভবিষ্যতে কী উন্নতি করবে?

**✅ উত্তর:**
1. **CMS Integration** — Admin panel থেকে Projects/Blog পরিবর্তন করা যাবে
2. **Blog Section** — Technical articles প্রকাশ করা
3. **Live Chat** — Client real-time support
4. **Dark/Light Mode Toggle** — User choice
5. **i18n (Internationalization)** — বাংলা ভার্সন
6. **Backend CRM** — Client inquiry manage করা

---

### ❓ প্রশ্ন ২৮: এই ইন্টার্নশিপে তুমি কী শিখলে?

**✅ উত্তর:**  
1. **Professional Animation** — GSAP দিয়ে industry-standard animation তৈরি
2. **CSS Architecture** — CSS Variables দিয়ে maintainable Design System
3. **Performance Optimization** — CDN, lazy loading, GPU animation
4. **UX Design** — Preloader patience UX, hover states, mobile nav
5. **Graceful Degradation** — Library fail হলেও site কার্যকর রাখা
6. **Responsive Design** — Mobile-first, Media Queries
7. **PHP Backend** — Form handling, file upload

---

## 📌 বোনাস: প্যানেল থেকে হঠাৎ করা প্রশ্ন

---

### ❓ Flexbox আর Grid-এর পার্থক্য?

**✅ উত্তর:**  
| | Flexbox | Grid |
|--|---------|------|
| Direction | ১ মাত্রা (row বা column) | ২ মাত্রা (row + column) |
| ব্যবহার | Navbar, card row | Dashboard, complex layout |
| আমার ব্যবহার | Hero section, navbar | Stats section (4 columns), Service grid |

---

### ❓ CSS `transition` আর `animation`-এর পার্থক্য?

**✅ উত্তর:**  
| | `transition` | `animation` |
|--|-------------|-------------|
| চালু হয় | State change-এ (hover, click) | Loading বা নির্দিষ্ট সময়ে |
| লুপ | পারে না | `infinite` দিয়ে পারে |
| Keyframe | নেই | আছে (`@keyframes`) |
| আমার ব্যবহার | Navbar color, button hover | Marquee, Hero Grid, Gradient BG |

---

### ❓ `em` আর `rem` কী?

**✅ উত্তর:**  
- `em` — parent element-এর font-size এর relative
- `rem` — root (`html`) element-এর font-size এর relative (সাধারণত 16px)
- `rem` বেশি predictable কারণ parent নির্ভর নয়

---

> 📝 **নোট:** এই ডকুমেন্টটি **Krelyon Agency Website** প্রজেক্টের ইন্টার্নশিপ ডিফেন্সের জন্য প্রস্তুত।  
> **প্রজেক্ট পাথ:** `/Applications/MAMP/htdocs/Krelyon-v3/`  
> **ডেভেলপার:** শরীফউল্লাহ ঢালী  
> **তারিখ:** ফেব্রুয়ারি ২০২৬

