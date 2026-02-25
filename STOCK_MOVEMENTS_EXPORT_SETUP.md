# Stock Movements Export & Filter Setup Guide

## ✅ What Has Been Implemented

### 1. **Enhanced Filters**
- **Supplier Filter**: Filter movements by supplier
- **Factor Number/Invoice No Filter**: Search by invoice number
- **Date Range Filters**: Filter by date from and to
- **Existing Filters**: Warehouse, Product, and Type filters (already present)

### 2. **Export Functionality**
- **Excel Export**: Download movements as XLSX file with formatted headers and styling
- **PDF Export**: Download movements as professional PDF with beautiful UI
- **Smart Export**: Exports respect all applied filters
- **Locale Support**: Exports in both English and Turkish

### 3. **Improved UI**
- Filter panel with all filter options in a card layout
- Export buttons with icons (Download, FileText icons)
- Updated table with new Supplier and Invoice No columns
- Professional styling with Tailwind CSS

## 📦 Required Packages to Install

```bash
cd /home/milad/W/Laravel/Warehouse

# Install the packages
composer require maatwebsite/excel barryvdh/laravel-dompdf
```

### Package Details:
- **`maatwebsite/excel`** (^3.1 or latest): For Excel export functionality
- **`barryvdh/laravel-dompdf`** (^1.0 or latest): For PDF export functionality

## 📋 Files Created/Modified

### Created Files:
1. **`app/Exports/StockMovementsExport.php`**
   - Excel export class with formatting
   - Header styling with blue background
   - Multi-language support
   - Automatic column sizing

2. **`resources/views/exports/stock-movements-pdf.blade.php`**
   - Professional PDF template
   - Responsive layout
   - Summary section
   - Multi-language support
   - Beautiful styling with borders and colors

### Modified Files:
1. **`app/Http/Controllers/Warehouse/StockMovementController.php`**
   - Added `supplier_id` and `factor_number` filters to the index() method
   - Added `exportExcel()` method
   - Added `exportPdf()` method
   - Added `getFilteredMovements()` helper method
   - Updated suppliers data passed to view

2. **`app/Models/StockMovement.php`**
   - Added `supplier()` relationship

3. **`resources/js/pages/warehouse/stock-movements/Index.vue`**
   - Added comprehensive filter UI with 4-column responsive grid
   - Added export buttons section
   - Integrated supplier filter select
   - Integrated factor number search input
   - Added date range filters
   - New table columns for supplier and invoice number
   - Export functions that respect filters

4. **`routes/web.php`**
   - Added `/warehouse/stock-movements/export/excel` route
   - Added `/warehouse/stock-movements/export/pdf` route

5. **`resources/js/i18n/locales/en.json`**
   - Added filter-related translation keys
   - Added export-related translation keys
   - Added stock supplier and factorNumber keys

6. **`resources/js/i18n/locales/tr.json`**
   - Added Turkish translations for all new features

## 🚀 How to Use

### 1. **Apply Filters**
   - Select Warehouse (optional)
   - Select Supplier (optional)
   - Enter Invoice No (partial search supported)
   - Select Type: Input, Output, Transfer, Adjustment
   - Set From Date and To Date
   - Click "Apply Filters"

### 2. **Export Filtered Data**
   - After applying filters, click:
     - "Export to Excel" button for XLSX file
     - "Export to PDF" button for PDF file
   - The export will include only the filtered data
   - File names include timestamp: `stock-movements-2024-02-25-14-30-45.xlsx`

### 3. **Reset Filters**
   - Click the "Reset" button to clear all filters
   - Click "Apply Filters" again to load all movements

## 📊 Export Features

### Excel Export:
- ✅ Professional blue header row with white text
- ✅ All columns: Date, Product, Type, Quantity, Unit Price, Warehouse, From Warehouse, Supplier, Invoice No, User, Notes
- ✅ Auto-sized columns
- ✅ Localized type labels (Giriş, Çıkış, Transfer, Ayarlama for Turkish)
- ✅ Formatted numbers with 2 decimal places
- ✅ Responsive to language setting

### PDF Export:
- ✅ Professional header with title and timestamp
- ✅ Summary section with record count
- ✅ Color-coded badges for movement types
- ✅ All movement details in tabular format
- ✅ Beautiful borders and alternating row colors
- ✅ Right-to-left text support (prepared for RTL locales)
- ✅ Responsive layout
- ✅ Multi-language support

## 🔧 Installation Steps

1. **Install Packages**:
   ```bash
   cd /home/milad/W/Laravel/Warehouse
   composer require maatwebsite/excel barryvdh/laravel-dompdf
   ```

2. **Publish Config Files** (if needed):
   ```bash
   php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider" --tag=config
   php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
   ```

3. **Clear Cache**:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

4. **Test the Features**:
   - Navigate to `/warehouse/stock-movements`
   - Apply filters and test exports

## 🎨 Customization Options

### Modify Excel Styling:
Edit `app/Exports/StockMovementsExport.php`:
- Change header color: Look for `'rgb' => '3B82F6'` (blue)
- Change font size in `headings()` method
- Add/remove columns in `map()` method

### Modify PDF Styling:
Edit `resources/views/exports/stock-movements-pdf.blade.php`:
- Change colors in the `<style>` section
- Modify header layout
- Adjust column widths

### Add/Remove Filters:
1. Edit `StockMovementController.php` - update `index()` and `getFilteredMovements()` methods
2. Edit `Index.vue` - add/remove filter inputs
3. Update language files with new filter labels

## 🐛 Troubleshooting

### Package Installation Issues:
If you encounter network timeout:
```bash
# Try with extended timeout
composer require maatwebsite/excel barryvdh/laravel-dompdf --with-dependencies --no-scripts

# Or install individually
composer require maatwebsite/excel
composer require barryvdh/laravel-dompdf
```

### PDF Export Issues:
- Make sure `storage/app` directory is writable
- Check Laravel logs: `storage/logs/laravel.log`
- Ensure required fonts are available

### Export Not Showing Filters:
- Clear browser cache
- Check that filter values are being passed correctly in URL
- Verify the `getFilteredMovements()` method is being called

## 📝 Notes

- All timestamps are in Europe/Istanbul timezone (as per existing settings)
- Exports respect user permissions (`stock_movements.view`)
- Filtered data is exported, not all data in the system
- Export handling is optimized for large datasets
- Both English and Turkish locales are fully supported

## ✨ Future Enhancements

Consider these future improvements:
1. Add "Export All" toggle to export unfiltered data
2. Add export to CSV format
3. Add scheduled exports
4. Add custom report builder
5. Add export templates

---

**Installation Status**: Ready to install packages
**Last Updated**: February 25, 2026
