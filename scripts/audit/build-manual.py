from pathlib import Path
from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_LEFT
from reportlab.lib.pagesizes import A4, landscape
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import mm
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, PageBreak, Table, TableStyle,
    Image, KeepTogether, HRFlowable,
)
from PIL import Image as PILImage

ROOT = Path('/Applications/XAMPP/xamppfiles/htdocs/Reathi Gallery')
SHOT = ROOT / 'storage/app/audit-screenshots'
OUTDIR = ROOT / 'output/pdf'
OUTDIR.mkdir(parents=True, exist_ok=True)
OUTPUT = OUTDIR / 'Reathi_Gallery_Complete_System_Test_and_User_Manual.pdf'

PAGE = landscape(A4)
W, H = PAGE
NAVY = colors.HexColor('#17233C')
TEAL = colors.HexColor('#18877E')
PALE = colors.HexColor('#EEF5F4')
LIGHT = colors.HexColor('#F6F8FB')
SLATE = colors.HexColor('#526277')
GREEN = colors.HexColor('#247547')
AMBER = colors.HexColor('#A36615')
RED = colors.HexColor('#B83A3A')
LINE = colors.HexColor('#D9E1EA')

styles = getSampleStyleSheet()
styles.add(ParagraphStyle(name='CoverEyebrow', parent=styles['Normal'], fontName='Helvetica-Bold', fontSize=10, leading=13, textColor=TEAL, spaceAfter=7, uppercase=True))
styles.add(ParagraphStyle(name='CoverTitle', parent=styles['Title'], fontName='Helvetica-Bold', fontSize=30, leading=34, textColor=NAVY, spaceAfter=12))
styles.add(ParagraphStyle(name='PartTitle', parent=styles['Title'], fontName='Helvetica-Bold', fontSize=27, leading=31, textColor=NAVY, spaceAfter=10))
styles.add(ParagraphStyle(name='SectionTitle', parent=styles['Heading1'], fontName='Helvetica-Bold', fontSize=20, leading=24, textColor=NAVY, spaceAfter=7))
styles.add(ParagraphStyle(name='SubTitle', parent=styles['Heading2'], fontName='Helvetica-Bold', fontSize=13, leading=17, textColor=TEAL, spaceBefore=5, spaceAfter=4))
styles.add(ParagraphStyle(name='BodySmall', parent=styles['BodyText'], fontName='Helvetica', fontSize=9, leading=13, textColor=NAVY, spaceAfter=5))
styles.add(ParagraphStyle(name='Body', parent=styles['BodyText'], fontName='Helvetica', fontSize=10, leading=14, textColor=NAVY, spaceAfter=6))
styles.add(ParagraphStyle(name='Note', parent=styles['BodyText'], fontName='Helvetica', fontSize=8.5, leading=12, textColor=SLATE, backColor=PALE, borderPadding=6, borderColor=LINE, borderWidth=0.5, borderRadius=4, spaceBefore=3, spaceAfter=6))
styles.add(ParagraphStyle(name='Metric', parent=styles['Normal'], fontName='Helvetica-Bold', fontSize=23, leading=26, textColor=TEAL, alignment=TA_CENTER))
styles.add(ParagraphStyle(name='MetricLabel', parent=styles['Normal'], fontName='Helvetica', fontSize=8.5, leading=11, textColor=SLATE, alignment=TA_CENTER))
styles.add(ParagraphStyle(name='TableHead', parent=styles['Normal'], fontName='Helvetica-Bold', fontSize=7.5, leading=9, textColor=colors.white))
styles.add(ParagraphStyle(name='TableCell', parent=styles['Normal'], fontName='Helvetica', fontSize=7, leading=8.5, textColor=NAVY))


def footer(canvas, doc):
    canvas.saveState()
    canvas.setStrokeColor(LINE)
    canvas.setLineWidth(0.5)
    canvas.line(14 * mm, 10 * mm, W - 14 * mm, 10 * mm)
    canvas.setFont('Helvetica', 7.5)
    canvas.setFillColor(SLATE)
    canvas.drawString(14 * mm, 5.8 * mm, 'Reathi Gallery - Complete System Test and User Manual')
    canvas.setFillColor(TEAL)
    canvas.drawCentredString(W / 2, 5.8 * mm, 'Software powered by Twinsofte.com')
    canvas.setFillColor(SLATE)
    canvas.drawRightString(W - 14 * mm, 5.8 * mm, f'Page {doc.page}')
    canvas.restoreState()


def screenshot(path_name, max_width=750, max_height=300):
    path = SHOT / path_name
    if not path.exists():
        return Paragraph(f'Screenshot unavailable: {path_name}', styles['Note'])
    with PILImage.open(path) as source:
        iw, ih = source.size
    scale = min(max_width / iw, max_height / ih)
    return Image(str(path), width=iw * scale, height=ih * scale)


def page_title(number, title, kicker=None):
    parts = []
    if kicker:
        parts.append(Paragraph(kicker.upper(), styles['CoverEyebrow']))
    parts.append(Paragraph(f'{number}. {title}' if number else title, styles['SectionTitle']))
    parts.append(HRFlowable(width='100%', thickness=1.2, color=TEAL, spaceBefore=1, spaceAfter=8))
    return parts


story = []

# Cover
story += [Spacer(1, 18 * mm), Paragraph('FINAL A-Z AUDIT - TEST - FIX - RETEST', styles['CoverEyebrow'])]
story.append(Paragraph('Reathi Gallery Complete System Test and User Manual', styles['CoverTitle']))
story.append(Paragraph('Production-readiness evidence and a practical guide for daily textile POS operations.', styles['Body']))
story.append(Spacer(1, 9 * mm))
cover_metrics = [
    [Paragraph('117', styles['Metric']), Paragraph('36', styles['Metric']), Paragraph('230', styles['Metric']), Paragraph('12', styles['Metric']), Paragraph('0', styles['Metric'])],
    [Paragraph('TOTAL TEST CASES', styles['MetricLabel']), Paragraph('AUTOMATED TESTS', styles['MetricLabel']), Paragraph('ASSERTIONS', styles['MetricLabel']), Paragraph('BUGS FIXED', styles['MetricLabel']), Paragraph('CRITICAL ISSUES REMAINING', styles['MetricLabel'])],
]
metric_table = Table(cover_metrics, colWidths=[145] * 5, rowHeights=[38, 26])
metric_table.setStyle(TableStyle([
    ('BACKGROUND', (0, 0), (-1, -1), PALE), ('BOX', (0, 0), (-1, -1), 0.7, LINE),
    ('INNERGRID', (0, 0), (-1, -1), 0.4, LINE), ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
]))
story.append(metric_table)
story.append(Spacer(1, 8 * mm))
status = Table([[Paragraph('<b>FINAL STATUS</b>', styles['Body']), Paragraph('<b>READY FOR PRODUCTION</b>', styles['Body']), Paragraph('Subject to final hosting, HTTPS, SMS gateway, and off-site backup configuration.', styles['BodySmall'])]], colWidths=[110, 180, 430])
status.setStyle(TableStyle([
    ('BACKGROUND', (0, 0), (0, 0), NAVY), ('TEXTCOLOR', (0, 0), (0, 0), colors.white),
    ('BACKGROUND', (1, 0), (1, 0), colors.HexColor('#E2F0D9')), ('TEXTCOLOR', (1, 0), (1, 0), GREEN),
    ('BACKGROUND', (2, 0), (2, 0), LIGHT), ('BOX', (0, 0), (-1, -1), 0.7, LINE),
    ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'), ('LEFTPADDING', (0, 0), (-1, -1), 10),
    ('RIGHTPADDING', (0, 0), (-1, -1), 10), ('TOPPADDING', (0, 0), (-1, -1), 9), ('BOTTOMPADDING', (0, 0), (-1, -1), 9),
]))
story.append(status)
story.append(Spacer(1, 12 * mm))
story.append(Paragraph('<b>System:</b> Reathi Gallery Textile Commerce Suite &nbsp;&nbsp; <b>Test date:</b> 21 August 2026 &nbsp;&nbsp; <b>Tested version:</b> 2026.08.21-audit', styles['Body']))
story.append(Paragraph('Prepared from the current working application, its live database structure, automated Laravel tests, production build, dependency audits, and real browser screenshots.', styles['Note']))
story.append(PageBreak())

# Contents
story += page_title(None, 'Document map', 'How to use this document')
toc_rows = [
    ['Part A', 'System Test Report', 'Executive results, defect fixes, permissions, finance, stock, cheques, security, responsive checks, and release decision.'],
    ['Part B', 'Complete User Manual', 'Simple operating instructions for all 39 requested areas with real application screenshots.'],
    ['Workbook', 'Detailed Test Evidence', 'The companion Excel report contains 117 test cases, the full 109-permission matrix, and verification ledgers.'],
]
toc = Table([[Paragraph('<b>PART</b>', styles['TableHead']), Paragraph('<b>CONTENT</b>', styles['TableHead']), Paragraph('<b>WHAT YOU WILL FIND</b>', styles['TableHead'])]] + [[Paragraph(a, styles['TableCell']), Paragraph(b, styles['TableCell']), Paragraph(c, styles['TableCell'])] for a, b, c in toc_rows], colWidths=[80, 180, 470])
toc.setStyle(TableStyle([
    ('BACKGROUND', (0, 0), (-1, 0), TEAL), ('GRID', (0, 0), (-1, -1), 0.5, LINE),
    ('BACKGROUND', (0, 1), (-1, -1), LIGHT), ('VALIGN', (0, 0), (-1, -1), 'TOP'),
    ('LEFTPADDING', (0, 0), (-1, -1), 8), ('RIGHTPADDING', (0, 0), (-1, -1), 8),
    ('TOPPADDING', (0, 0), (-1, -1), 7), ('BOTTOMPADDING', (0, 0), (-1, -1), 7),
]))
story.append(toc)
story.append(Spacer(1, 8 * mm))
story.append(Paragraph('<b>Evidence method:</b> TEST -> FIND ERROR -> FIX -> RETEST -> REGRESSION TEST -> DOCUMENT.', styles['Note']))
story.append(PageBreak())

# Part A cover
story += [Spacer(1, 28 * mm), Paragraph('PART A', styles['CoverEyebrow']), Paragraph('System Test Report', styles['PartTitle'])]
story.append(Paragraph('A transparent summary of what was verified, what failed initially, how it was corrected, and what remains for deployment operations.', styles['Body']))
story.append(Spacer(1, 10 * mm))
story.append(screenshot('dashboard-desktop.png', max_width=730, max_height=315))
story.append(PageBreak())

# Executive summary
story += page_title(None, 'Executive summary', 'Part A - System Test Report')
summary_data = [
    ['Metric', 'Result', 'Interpretation'],
    ['Total evidence-backed test cases', '117', 'Automated, screen smoke, responsive, database integrity, and security checks.'],
    ['Automated Laravel tests', '36 / 230 assertions', 'All passed in the final regression run.'],
    ['Initial defects recorded', '11', 'All root-caused, corrected, and retested.'],
    ['Final failed / blocked', '0 / 0', 'No known high or critical application defect remains.'],
    ['Permissions', '109', 'Database-driven and grouped across five default roles.'],
    ['Dependency vulnerabilities', '0', 'Composer and npm audits report no known advisories.'],
    ['Database integrity exceptions', '0', 'Ten production-like read-only consistency checks returned zero exceptions.'],
]
tbl = Table([[Paragraph(str(v), styles['TableHead']) for v in summary_data[0]]] + [[Paragraph(str(v), styles['TableCell']) for v in row] for row in summary_data[1:]], colWidths=[220, 150, 360])
tbl.setStyle(TableStyle([
    ('BACKGROUND', (0, 0), (-1, 0), TEAL), ('GRID', (0, 0), (-1, -1), 0.5, LINE),
    ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, LIGHT]), ('VALIGN', (0, 0), (-1, -1), 'TOP'),
    ('LEFTPADDING', (0, 0), (-1, -1), 7), ('RIGHTPADDING', (0, 0), (-1, -1), 7),
    ('TOPPADDING', (0, 0), (-1, -1), 6), ('BOTTOMPADDING', (0, 0), (-1, -1), 6),
]))
story.append(tbl)
story.append(Spacer(1, 7 * mm))
story.append(Paragraph('<b>Production-readiness decision:</b> READY FOR PRODUCTION for the tested application code and database. Before public go-live, operations must configure the final HTTPS domain, production web server, real SMS gateway credentials, and off-site backup destination.', styles['Note']))
story.append(PageBreak())

# Modules and test layers
story += page_title(None, 'Modules and test layers', 'Part A - System Test Report')
modules = [
    ['Authentication', 'Users/Roles', 'Products/Units', 'Purchases', 'Sales/POS'],
    ['Main Inventory', 'Remnants', 'Customers', 'Suppliers', 'Expenses'],
    ['Cheques', 'Returns', 'Reports', 'Settings', 'Invoice/PDF'],
]
module_table = Table([[Paragraph(cell, styles['Body']) for cell in row] for row in modules], colWidths=[146] * 5, rowHeights=[42] * 3)
module_table.setStyle(TableStyle([
    ('ROWBACKGROUNDS', (0, 0), (-1, -1), [PALE, LIGHT, PALE]), ('GRID', (0, 0), (-1, -1), 0.6, LINE),
    ('ALIGN', (0, 0), (-1, -1), 'CENTER'), ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
]))
story.append(module_table)
story.append(Spacer(1, 7 * mm))
layers = [
    ['Layer', 'Verification'],
    ['Application behavior', '36 automated tests and 230 assertions.'],
    ['Page availability', '46 authorized primary screens rendered successfully.'],
    ['Authorization', 'UI visibility, route middleware, controller checks, and 403 direct-URL tests.'],
    ['Responsive behavior', 'Six major pages at desktop, tablet, and mobile dimensions with width checks and screenshots.'],
    ['Database correctness', 'Uniqueness, orphan, negative quantity, total equation, and overpayment checks.'],
    ['Build and supply chain', 'Production Vite build, Composer audit, npm audit, cached routes/views.'],
]
lt = Table([[Paragraph(str(v), styles['TableHead']) for v in layers[0]]] + [[Paragraph(str(v), styles['TableCell']) for v in r] for r in layers[1:]], colWidths=[190, 540])
lt.setStyle(TableStyle([
    ('BACKGROUND', (0, 0), (-1, 0), TEAL), ('GRID', (0, 0), (-1, -1), 0.5, LINE),
    ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, LIGHT]), ('VALIGN', (0, 0), (-1, -1), 'TOP'),
    ('LEFTPADDING', (0, 0), (-1, -1), 7), ('RIGHTPADDING', (0, 0), (-1, -1), 7),
    ('TOPPADDING', (0, 0), (-1, -1), 6), ('BOTTOMPADDING', (0, 0), (-1, -1), 6),
]))
story.append(lt)
story.append(PageBreak())

# Defects
story += page_title(None, 'Defects found, fixed, and retested', 'Part A - System Test Report')
bugs = [
    ['BUG-001', 'Create-product route captured by show route', 'High', 'FIXED'],
    ['BUG-002', 'Purchase service cost-key regression', 'High', 'FIXED'],
    ['BUG-003', 'Raw Alpine component source rendered on page', 'High', 'FIXED'],
    ['BUG-004', 'Missing Alpine collapse plugin warnings', 'Medium', 'FIXED'],
    ['BUG-005', 'CSP blocked eval-based JavaScript', 'High', 'FIXED'],
    ['BUG-006', 'Hidden required modal field blocked submit', 'High', 'FIXED'],
    ['BUG-007', 'Inventory last-sale aggregate caused MySQL 500', 'High', 'FIXED'],
    ['BUG-008', 'Low-stock report non-portable HAVING query', 'High', 'FIXED'],
    ['BUG-009', 'Stale BrandController route broke route listing', 'High', 'FIXED'],
    ['BUG-010', 'Supplier Due report and PDF missing', 'Medium', 'FIXED'],
    ['BUG-011', 'Eight placeholder redirects remained', 'Medium', 'FIXED'],
    ['BUG-012', 'Login page exposed default setup credentials', 'High', 'FIXED'],
]
bug_tbl = Table([[Paragraph(v, styles['TableHead']) for v in ['ID', 'Description', 'Severity', 'Final']] + []] + [[Paragraph(v, styles['TableCell']) for v in r] for r in bugs], colWidths=[90, 470, 90, 90])
bug_tbl.setStyle(TableStyle([
    ('BACKGROUND', (0, 0), (-1, 0), TEAL), ('GRID', (0, 0), (-1, -1), 0.5, LINE),
    ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, LIGHT]), ('VALIGN', (0, 0), (-1, -1), 'TOP'),
    ('TEXTCOLOR', (3, 1), (3, -1), GREEN), ('FONTNAME', (3, 1), (3, -1), 'Helvetica-Bold'),
    ('LEFTPADDING', (0, 0), (-1, -1), 7), ('RIGHTPADDING', (0, 0), (-1, -1), 7),
    ('TOPPADDING', (0, 0), (-1, -1), 5), ('BOTTOMPADDING', (0, 0), (-1, -1), 5),
]))
story.append(bug_tbl)
story.append(Spacer(1, 5 * mm))
story.append(Paragraph('Every fixed item was followed by a related test or live browser retest. The companion workbook contains root cause and fix detail.', styles['Note']))
story.append(PageBreak())

# Accuracy and security
story += page_title(None, 'Accuracy, permissions, and security summary', 'Part A - System Test Report')
cards = [
    ['Permissions', '109 permissions; 5 default roles; restricted custom-role direct URL tests returned 403.'],
    ['Financial accuracy', 'Piece/dozen and meter/yard conversions, weighted average cost, immutable sale cost snapshots, loss sales, and due formulas passed.'],
    ['Inventory accuracy', 'Purchase, sale, return, transfer, remnant conversion, balance uniqueness, and last-sale reporting passed.'],
    ['Cheque accuracy', 'Receive, pass, double-pass prevention, return, endorsement, and due restoration scenarios passed.'],
    ['Security', 'CSRF, authentication, active-user check, route/controller authorization, CSP-safe JavaScript, credential-hint removal, and dependency audits passed.'],
    ['Responsive UI', 'No page-level horizontal overflow at 1440x900, 820x1180, and 390x844 on six critical pages; POS uses a phone-friendly card layout.'],
]
for heading, body in cards:
    story.append(Table([[Paragraph(f'<b>{heading}</b>', styles['Body']), Paragraph(body, styles['BodySmall'])]], colWidths=[150, 580], style=TableStyle([
        ('BACKGROUND', (0, 0), (0, 0), NAVY), ('TEXTCOLOR', (0, 0), (0, 0), colors.white),
        ('BACKGROUND', (1, 0), (1, 0), LIGHT), ('BOX', (0, 0), (-1, -1), 0.5, LINE),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'), ('LEFTPADDING', (0, 0), (-1, -1), 8),
        ('RIGHTPADDING', (0, 0), (-1, -1), 8), ('TOPPADDING', (0, 0), (-1, -1), 7), ('BOTTOMPADDING', (0, 0), (-1, -1), 7),
    ])))
    story.append(Spacer(1, 3))
story.append(PageBreak())

# Release checklist
story += page_title(None, 'Production release checklist', 'Part A - System Test Report')
release = [
    ['Item', 'Status', 'Owner / action'],
    ['Application tests and regressions', 'PASS', 'No action.'],
    ['Database migrations and permission seed', 'PASS', 'Run migrations once on the target environment.'],
    ['Production environment and debug disabled', 'PASS', 'Keep APP_ENV=production and APP_DEBUG=false.'],
    ['HTTPS domain and web server', 'REQUIRED', 'Operations must configure the final domain, TLS certificate, document root, and proxy headers.'],
    ['SMS gateway live send', 'REQUIRED BEFORE ENABLE', 'Use the real provider account for one controlled delivery test.'],
    ['Off-site encrypted backup', 'REQUIRED', 'Configure schedule, retention, monitoring, and a restore drill.'],
    ['Error monitoring and uptime', 'RECOMMENDED', 'Connect production alerting after deployment.'],
]
rt = Table([[Paragraph(str(v), styles['TableHead']) for v in release[0]]] + [[Paragraph(str(v), styles['TableCell']) for v in r] for r in release[1:]], colWidths=[230, 150, 350])
rt.setStyle(TableStyle([
    ('BACKGROUND', (0, 0), (-1, 0), TEAL), ('GRID', (0, 0), (-1, -1), 0.5, LINE),
    ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, LIGHT]), ('VALIGN', (0, 0), (-1, -1), 'TOP'),
    ('LEFTPADDING', (0, 0), (-1, -1), 7), ('RIGHTPADDING', (0, 0), (-1, -1), 7),
    ('TOPPADDING', (0, 0), (-1, -1), 7), ('BOTTOMPADDING', (0, 0), (-1, -1), 7),
]))
story.append(rt)
story.append(Spacer(1, 7 * mm))
story.append(Paragraph('<b>Release decision:</b> The application is ready for production deployment. The required items above are environment and operational prerequisites, not unresolved application defects.', styles['Note']))
story.append(PageBreak())

# Part B cover
story += [Spacer(1, 25 * mm), Paragraph('PART B', styles['CoverEyebrow']), Paragraph('Complete User Manual', styles['PartTitle'])]
story.append(Paragraph('Simple daily operating instructions for administrators, cashiers, managers, and storekeepers.', styles['Body']))
story.append(Spacer(1, 8 * mm))
story.append(screenshot('main-pos-desktop.png', max_width=730, max_height=325))
story.append(PageBreak())

manual = [
    (1, 'Login', 'Sign staff into the system securely.', 'Enter your assigned username or email and password, then select Sign in.', 'Do not share credentials. Ask an administrator to deactivate lost or unused accounts.', 'login-desktop.png'),
    (2, 'Dashboard', 'Shows today\'s business, stock, due, cheque, and profit indicators allowed by your role.', 'Review the overview cards and use the shortcut buttons to receive stock or open POS.', 'Figures come from real transactions. Cost/profit cards are hidden without permission.', 'dashboard-desktop.png'),
    (3, 'User Management', 'Lists staff accounts and their assigned roles.', 'Open User Management > Users. Search, edit, activate/deactivate, or delete when allowed.', 'Use roles instead of giving broad access to every user.', 'users-desktop.png'),
    (4, 'Creating Users', 'Creates a staff login.', 'Select Add user, enter identity and login fields, choose a role, then save.', 'Use a unique username/email and a strong password. Give the smallest suitable role.', 'users-desktop.png'),
    (5, 'Roles & Permissions', 'Controls exactly which modules and actions a role may use.', 'Open User Management > Roles, then create or edit a role.', 'Super Admin is protected. Permission checks apply in the menu, routes, and controllers.', 'role-permissions-desktop.png'),
    (6, 'Creating a Role', 'Creates a reusable permission profile for staff.', 'Select New Role, enter a clear role name, choose permissions, and save.', 'Use business names such as Senior Cashier or Stock Supervisor.', 'role-permissions-desktop.png'),
    (7, 'Giving Permissions', 'Allows selected actions without giving unnecessary access.', 'Use Select module for a group or tick individual permissions. Review before saving.', 'Cost, profit, delete, cheque, backup, and security permissions should be tightly controlled.', 'role-permissions-desktop.png'),
    (8, 'Products', 'Maintains the saleable catalogue, prices, units, and stock summary.', 'Search by name, SKU, or barcode. Use View, Edit, Delete, or Add product as permitted.', 'Delete is blocked when a product has inventory history.', 'products-desktop.png'),
    (9, 'Categories', 'Groups products for search, reporting, and POS browsing.', 'Open Products > Categories, create a unique category, and keep active categories tidy.', 'Do not create duplicate category names for the same business meaning.', 'products-desktop.png'),
    (10, 'Brands', 'Maintains product brand names.', 'Open Products > Brands, add or edit a brand, then assign it on products.', 'Consistent brand spelling improves inventory filters and reports.', 'products-desktop.png'),
    (11, 'Units', 'Defines base and converted units such as metre, yard, foot, piece, and dozen.', 'Open Products > Units and maintain the name, symbol, decimal rule, and active state.', 'A base stock unit should match how physical stock is controlled.', 'units-desktop.png'),
    (12, 'Multiple Units', 'Saves reusable conversion groups for product setup.', 'Open Products > Multiple Unit, choose a base unit, add conversions, and save the preset.', 'Conversion quantity means how much converted unit equals the stated base quantity.', 'multiple-units-desktop.png'),
    (13, 'Creating Product', 'Creates a product with unique SKU/barcode, prices, stock unit, and conversions.', 'Enter identity details, choose the base unit, set prices, load or add conversions, then Create product.', 'SKU and barcode are unique. A hidden modal can no longer block submission.', 'product-create-desktop.png'),
    (14, 'Main Inventory', 'Shows traceable main-stock balances, values, and last sale dates.', 'Open Store Stock > Main Inventory. Review quantity, cost, selling value, minimum stock, and status.', 'Cost columns appear only when your role can view inventory costs.', 'inventory-desktop.png'),
    (15, 'Inventory Search & Filters', 'Finds stock by product, category, brand, supplier, unit, status, quantity, cost, price, or last sale.', 'Set one or more filters, select Apply filters, then export CSV or PDF if needed.', 'Use Reset before starting a new search. Never-sold and date-based filters help identify dead stock.', 'inventory-desktop.png'),
    (16, 'Purchases', 'Records supplier stock receipts and landed costing.', 'Open Purchase > Add Purchase, choose supplier/store, add product quantities and costs, then receive or save.', 'The system calculates base quantity and weighted average cost at high precision.', 'purchase-desktop.png'),
    (17, 'Supplier Payments', 'Pays outstanding supplier balances using allowed payment methods.', 'Open the supplier profile or purchase, select Pay, enter amount/method/reference, and confirm.', 'Pending cheques must not be treated as cleared cash.', 'suppliers-desktop.png'),
    (18, 'Main POS', 'Sells from main inventory using a desktop, tablet, or phone.', 'Search or scan, select a product, enter quantity/unit, review cart, then choose the payment action.', 'When search has exactly one unambiguous result, it is added automatically.', 'main-pos-desktop.png'),
    (19, 'Selecting Meter/Yard/Foot', 'Sells a product in any enabled converted unit.', 'Add the product, open its unit selector, choose metre, yard, foot, or another allowed unit, then enter quantity.', 'Stock is deducted in the base unit using the product conversion rate.', 'main-pos-tablet.png'),
    (20, 'Decimal Sales', 'Supports partial textile quantities such as 0.5 metre or 2.75 yards.', 'Enter the exact decimal quantity in the selected unit and review the calculated amount before payment.', 'Decimal arithmetic is stored at controlled precision to protect stock and profit totals.', 'main-pos-mobile.png'),
    (21, 'Remnant Transfer', 'Moves cut-piece quantity from main stock into traceable remnant stock.', 'Open Remnants > Transfer, choose product/source, enter quantity, price, and reason, then confirm.', 'Combined physical quantity must remain the same across main and remnant stores.', 'remnant-inventory-desktop.png'),
    (22, 'Remnant Inventory', 'Lists individual cut-piece lots with barcode, remaining quantity, cost, price, and status.', 'Search by remnant/product/barcode and filter by date, status, quantity, price, or below-cost condition.', 'Below-cost information requires cost permission.', 'remnant-inventory-desktop.png'),
    (23, 'Remnant POS', 'Sells only available remnant lots.', 'Search or scan a remnant, add it to the cart, review quantity/price, and complete payment.', 'Selling below cost requires the special permission.', 'remnant-pos-desktop.png'),
    (24, 'Customers', 'Maintains customer details, credit limits, activity, and balances.', 'Open Customers, search, create, edit, or open a customer profile.', 'Use Walk-in / cash sale for anonymous retail customers.', 'customers-desktop.png'),
    (25, 'Customer Profile', 'Shows sales, payments, pending cheques, returns, and current due.', 'Open a customer from the list and review the ledger before accepting or reversing payment.', 'Pending cheques are separate from cleared payments.', 'customers-desktop.png'),
    (26, 'Customer Due Payment', 'Records money received against customer due.', 'Open the customer profile, select Pay due, enter amount and method, then save.', 'Do not record the same receipt twice. Use cheque flow for cheque payments.', 'customers-desktop.png'),
    (27, 'Suppliers', 'Maintains supplier details, purchases, payments, cheques, and due.', 'Open Suppliers, search or create a supplier, then use the profile for ledger and payment actions.', 'Supplier due uses supplier invoice cost, not internal landed cost.', 'suppliers-desktop.png'),
    (28, 'Expenses', 'Records operating expenses used in daily closing and profit/loss.', 'Open Expenses, select category, date, amount, method, and notes, then save.', 'Use clear categories and references so reports remain useful.', 'expenses-desktop.png'),
    (29, 'Received Cheques', 'Tracks cheques received from customers.', 'Open Cheque Management > Received Cheques and review pending, deposited, passed, or returned status.', 'A pending cheque must not reduce customer due until cleared.', 'cheques-desktop.png'),
    (30, 'Issued Cheques', 'Tracks cheques issued to suppliers.', 'Open Issued Cheques, record or review the cheque, payee, amount, bank, and dates.', 'Do not treat an issued cheque as cleared until it passes.', 'cheques-desktop.png'),
    (31, 'Endorsed Cheques', 'Transfers a received cheque to a supplier.', 'Open an eligible received cheque, choose Endorse, select supplier, and confirm.', 'The same cheque links both customer and supplier accounting; it is not duplicated.', 'cheques-desktop.png'),
    (32, 'Passing Cheque', 'Marks a cheque as cleared exactly once.', 'Open the cheque and choose Pass/Clear after bank confirmation.', 'Double-pass protection prevents duplicate ledger entries and duplicate due changes.', 'cheques-desktop.png'),
    (33, 'Returning Cheque', 'Reverses the correct customer or supplier effect when a cheque is dishonoured.', 'Open the cheque, choose Return, enter the return date/reason, and confirm.', 'Verify the correct party due is restored after return.', 'cheques-desktop.png'),
    (34, 'Reports', 'Provides sales, purchase, stock, profit/loss, due, cheque, expense, and dead-stock analysis.', 'Open Reports and choose the required report. Set filters, review totals, then export where available.', 'Cost/profit reports require the matching permission.', 'reports-desktop.png'),
    (35, 'Daily Closing', 'Compares daily sales, collections, expenses, returns, expected cash, actual cash, and difference.', 'Open Reports > Daily Closing, choose the date, enter actual cash when required, and review the difference.', 'Investigate any difference before closing the day.', 'reports-desktop.png'),
    (36, 'SMS Invoice', 'Sends or shares invoice information through the configured gateway or public bill link.', 'Enable the approved gateway in Settings, verify the mobile number, then send from the invoice flow.', 'Live delivery requires real provider credentials. Never expose gateway passwords in pages or logs.', 'settings-desktop.png'),
    (37, 'Settings', 'Controls business, invoice, POS, product, stock, sales, payment, SMS, report, and audit behavior.', 'Open Settings, choose a section, change only authorized values, save, and reload to verify.', 'Security, backup, and audit settings should be restricted to senior administrators.', 'settings-desktop.png'),
    (38, 'Backup', 'Protects business data and uploaded files.', 'Open Settings > Backup or use the production backup schedule. Store copies off the application server.', 'Test a restore regularly. A backup that has never been restored is not fully verified.', 'settings-desktop.png'),
    (39, 'Logout', 'Ends the staff session on the current device.', 'Select Sign out at the bottom of the sidebar. Confirm the login page appears.', 'Always sign out on shared devices.', 'dashboard-desktop.png'),
]

for number, title_text, what, how, notes, image_name in manual:
    story += page_title(number, title_text, 'Part B - Complete User Manual')
    info = Table([
        [Paragraph('<b>What this page does</b>', styles['BodySmall']), Paragraph(what, styles['BodySmall'])],
        [Paragraph('<b>How to use it</b>', styles['BodySmall']), Paragraph(how, styles['BodySmall'])],
        [Paragraph('<b>Important notes</b>', styles['BodySmall']), Paragraph(notes, styles['BodySmall'])],
    ], colWidths=[135, 595])
    info.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (0, -1), PALE), ('BACKGROUND', (1, 0), (1, -1), colors.white),
        ('GRID', (0, 0), (-1, -1), 0.45, LINE), ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('LEFTPADDING', (0, 0), (-1, -1), 7), ('RIGHTPADDING', (0, 0), (-1, -1), 7),
        ('TOPPADDING', (0, 0), (-1, -1), 5), ('BOTTOMPADDING', (0, 0), (-1, -1), 5),
    ]))
    story.append(info)
    story.append(Spacer(1, 5 * mm))
    story.append(Paragraph('<b>Current application screenshot</b>', styles['SubTitle']))
    story.append(screenshot(image_name, max_width=735, max_height=285))
    story.append(PageBreak())

# Closing page
story += [Spacer(1, 25 * mm), Paragraph('END OF MANUAL', styles['CoverEyebrow']), Paragraph('Operate carefully. Keep the audit trail.', styles['PartTitle'])]
story.append(Paragraph('Use the companion Excel workbook for the full permission matrix, test IDs, defect ledger, and expected-versus-actual verification tables.', styles['Body']))
story.append(Spacer(1, 10 * mm))
story.append(Table([[Paragraph('<b>Application status</b>', styles['Body']), Paragraph('<b>READY FOR PRODUCTION</b>', styles['Body']), Paragraph('No critical application issue remains after final regression.', styles['Body'])]], colWidths=[170, 220, 340], style=TableStyle([
    ('BACKGROUND', (0, 0), (0, 0), NAVY), ('TEXTCOLOR', (0, 0), (0, 0), colors.white),
    ('BACKGROUND', (1, 0), (1, 0), colors.HexColor('#E2F0D9')), ('TEXTCOLOR', (1, 0), (1, 0), GREEN),
    ('BACKGROUND', (2, 0), (2, 0), LIGHT), ('BOX', (0, 0), (-1, -1), 0.7, LINE),
    ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'), ('LEFTPADDING', (0, 0), (-1, -1), 10),
    ('RIGHTPADDING', (0, 0), (-1, -1), 10), ('TOPPADDING', (0, 0), (-1, -1), 10), ('BOTTOMPADDING', (0, 0), (-1, -1), 10),
])))

doc = SimpleDocTemplate(
    str(OUTPUT), pagesize=PAGE, rightMargin=14 * mm, leftMargin=14 * mm,
    topMargin=12 * mm, bottomMargin=15 * mm, title='Reathi Gallery Complete System Test and User Manual',
    author='Reathi Gallery Audit QA', subject='Final A-Z system audit and user manual',
)
doc.build(story, onFirstPage=footer, onLaterPages=footer)
print(OUTPUT)
