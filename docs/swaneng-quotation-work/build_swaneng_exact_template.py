from pathlib import Path
from shutil import copy2
from PIL import Image, ImageChops
from docx import Document
from docx.shared import Pt, Inches, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_CELL_VERTICAL_ALIGNMENT
from docx.enum.section import WD_SECTION
from docx.oxml import OxmlElement
from docx.oxml.ns import qn

ROOT = Path(__file__).resolve().parents[2]
REF = ROOT/'docs'/'Kweneng_Public_Website_Quotation_2026-08-06.docx'
OUT = ROOT/'docs'/'Swaneng_Website_Development_Quotation_2026-08-10.docx'
LOGO_SRC = Path(r'C:\Users\mmusi\Downloads\Black White Minimalist Professional Initial Logo\Black White Minimalist Professional Initial Logo.png')
LOGO = ROOT/'docs'/'swaneng-quotation-work'/'digitalcore-logo-cropped.png'

# Crop excess white space from the supplied logo without altering its artwork.
im = Image.open(LOGO_SRC).convert('RGB')
bg = Image.new('RGB', im.size, 'white')
bbox = ImageChops.difference(im, bg).getbbox()
if bbox:
    im.crop(bbox).save(LOGO)
else:
    im.save(LOGO)

copy2(REF, OUT)
doc = Document(OUT)
body=doc._element.body; sect=body.sectPr
for child in list(body):
    if child is not sect: body.remove(child)

NAVY='0F2B4D'; BLUE='2E75B6'; GREEN='2E8B57'; PALE='E7EEF6'; LIGHT='F3F5F8'; TEXT=RGBColor(35,42,52)

def shade(cell, fill):
    pr=cell._tc.get_or_add_tcPr(); sh=pr.find(qn('w:shd'))
    if sh is None: sh=OxmlElement('w:shd'); pr.append(sh)
    sh.set(qn('w:fill'),fill)

def margins(cell, top=110, start=140, bottom=110, end=140):
    pr=cell._tc.get_or_add_tcPr(); cm=pr.first_child_found_in('w:tcMar')
    if cm is None: cm=OxmlElement('w:tcMar'); pr.append(cm)
    for n,v in [('top',top),('start',start),('bottom',bottom),('end',end)]:
        x=cm.find(qn('w:'+n))
        if x is None: x=OxmlElement('w:'+n); cm.append(x)
        x.set(qn('w:w'),str(v)); x.set(qn('w:type'),'dxa')

def borders(table, color='B8C6D8', size='5'):
    pr=table._tbl.tblPr; tb=pr.first_child_found_in('w:tblBorders')
    if tb is None: tb=OxmlElement('w:tblBorders'); pr.append(tb)
    for edge in ('top','left','bottom','right','insideH','insideV'):
        el=OxmlElement('w:'+edge); el.set(qn('w:val'),'single'); el.set(qn('w:sz'),size); el.set(qn('w:color'),color); tb.append(el)

def no_borders(table):
    pr=table._tbl.tblPr; tb=OxmlElement('w:tblBorders')
    for edge in ('top','left','bottom','right','insideH','insideV'):
        el=OxmlElement('w:'+edge); el.set(qn('w:val'),'nil'); tb.append(el)
    pr.append(tb)

def heading(text, level=1):
    p=doc.add_paragraph(style=f'Heading {level}'); p.paragraph_format.keep_with_next=True
    r=p.add_run(text); r.bold=True; r.font.color.rgb=RGBColor.from_string(BLUE)
    return p

def bullet(text):
    p=doc.add_paragraph(style='List Bullet'); p.paragraph_format.space_after=Pt(2); p.add_run(text); return p

def label_value(cell,label,value):
    p=cell.paragraphs[0]; p.paragraph_format.space_after=Pt(1)
    r=p.add_run(label.upper()); r.bold=True; r.font.size=Pt(8); r.font.color.rgb=RGBColor(100,110,125)
    p=cell.add_paragraph(); p.paragraph_format.space_after=Pt(0)
    r=p.add_run(value); r.bold=True; r.font.size=Pt(10); r.font.color.rgb=RGBColor.from_string(NAVY)

# Source-derived running header and footer.
header=doc.sections[0].header; hp=header.paragraphs[0]; hp.clear(); hp.alignment=WD_ALIGN_PARAGRAPH.LEFT
r=hp.add_run('DIGITALCORE BUSINESS SYSTEMS  |  WEBSITE SOLUTIONS'); r.bold=True; r.font.size=Pt(8); r.font.color.rgb=RGBColor(90,100,115)

# Cover label and supplied logo.
top=doc.add_table(rows=1,cols=2); top.autofit=False; top.alignment=WD_TABLE_ALIGNMENT.LEFT; no_borders(top)
top.columns[0].width=Inches(4.8); top.columns[1].width=Inches(1.6)
lp=top.cell(0,0).paragraphs[0]; lp.paragraph_format.space_before=Pt(34)
rr=lp.add_run('BUDGETARY QUOTATION'); rr.bold=True; rr.font.size=Pt(10); rr.font.color.rgb=RGBColor.from_string(GREEN)
lp=top.cell(0,0).add_paragraph('DigitalCore Business Systems'); lp.paragraph_format.space_after=Pt(0)
lp.runs[0].bold=True; lp.runs[0].font.size=Pt(11); lp.runs[0].font.color.rgb=RGBColor(90,100,115)
rp=top.cell(0,1).paragraphs[0]; rp.alignment=WD_ALIGN_PARAGRAPH.RIGHT
picture=rp.add_run().add_picture(str(LOGO), width=Inches(1.35))
doc_pr=picture._inline.docPr
doc_pr.set('descr','DigitalCore Business Systems logo')
doc_pr.set('title','DigitalCore Business Systems')

p=doc.add_paragraph(style='Title'); p.paragraph_format.space_before=Pt(20); p.paragraph_format.space_after=Pt(4)
p.add_run('School Website\nDevelopment')
p=doc.add_paragraph('Website development quotation for Swaneng English Medium Primary School')
p.runs[0].font.size=Pt(13); p.runs[0].font.color.rgb=RGBColor(100,110,125); p.paragraph_format.space_after=Pt(18)

meta=doc.add_table(rows=2,cols=2); meta.autofit=False; meta.alignment=WD_TABLE_ALIGNMENT.LEFT; no_borders(meta)
for row in meta.rows:
    for c in row.cells: margins(c,70,80,80,80); c.vertical_alignment=WD_CELL_VERTICAL_ALIGNMENT.TOP
label_value(meta.cell(0,0),'Prepared for','Swaneng English Medium Primary School')
label_value(meta.cell(0,1),'Quotation date','10 August 2026')
label_value(meta.cell(1,0),'Prepared by','DigitalCore Business Systems')
label_value(meta.cell(1,1),'Validity','30 calendar days')

doc.add_paragraph()
banner=doc.add_table(rows=1,cols=2); banner.autofit=False; banner.alignment=WD_TABLE_ALIGNMENT.LEFT; borders(banner,BLUE,'10')
banner.columns[0].width=Inches(4.2); banner.columns[1].width=Inches(2.2)
for c in banner.rows[0].cells: shade(c,PALE); margins(c,170,160,170,160); c.vertical_alignment=WD_CELL_VERTICAL_ALIGNMENT.CENTER
r=banner.cell(0,0).paragraphs[0].add_run('Fixed project investment'); r.bold=True; r.font.color.rgb=RGBColor.from_string(NAVY)
p=banner.cell(0,1).paragraphs[0]; p.alignment=WD_ALIGN_PARAGRAPH.RIGHT
r=p.add_run('BWP 7,500'); r.bold=True; r.font.size=Pt(16); r.font.color.rgb=RGBColor.from_string(NAVY)

heading('Proposed outcome',2)
doc.add_paragraph('DigitalCore Business Systems is pleased to provide this quotation for the development of a modern, professional and mobile-friendly website for Swaneng English Medium Primary School.')
doc.add_paragraph('The website will provide information about the school and will be easy to manage and update by authorised school staff.')

heading('Included features',2)
for x in ['Professional website design','Mobile-friendly and responsive layout','Home page','About the School page','Academic information pages','Contact page','News and announcements section','Photo gallery','Social media integration','Contact form','Administrator login for website management']:
    bullet(x)

doc.add_page_break()
heading('1. Website content management',1)
doc.add_paragraph('The website will be developed with a user-friendly administration panel that allows school staff to update the website without needing technical knowledge.')
heading('The school will be able to',2)
for x in ['Update website content and text','Upload and manage photos','Publish news and announcements','Update contact information','Add and edit pages as required']:
    bullet(x)

heading('2. Quotation',1)
qt=doc.add_table(rows=2,cols=2); qt.autofit=False; qt.alignment=WD_TABLE_ALIGNMENT.LEFT; borders(qt)
qt.columns[0].width=Inches(4.9); qt.columns[1].width=Inches(1.5)
for i,t in enumerate(['Description','Amount (BWP)']):
    shade(qt.cell(0,i),NAVY); margins(qt.cell(0,i),120,140,120,140)
    p=qt.cell(0,i).paragraphs[0]; p.alignment=WD_ALIGN_PARAGRAPH.LEFT if i==0 else WD_ALIGN_PARAGRAPH.RIGHT
    r=p.add_run(t); r.bold=True; r.font.color.rgb=RGBColor(255,255,255)
for c in qt.rows[1].cells: margins(c,140,140,140,140)
r=qt.cell(1,0).paragraphs[0].add_run('School Website Development'); r.bold=True; r.font.color.rgb=RGBColor.from_string(NAVY)
p=qt.cell(1,1).paragraphs[0]; p.alignment=WD_ALIGN_PARAGRAPH.RIGHT
r=p.add_run('P7,500'); r.bold=True; r.font.color.rgb=RGBColor.from_string(NAVY)

heading('Special package offer',2)
offer=doc.add_table(rows=1,cols=1); offer.alignment=WD_TABLE_ALIGNMENT.LEFT; borders(offer,GREEN,'10')
c=offer.cell(0,0); shade(c,LIGHT); margins(c,90,160,90,160)
p=c.paragraphs[0]; r=p.add_run('PACKAGE PRICE'); r.bold=True; r.font.size=Pt(8); r.font.color.rgb=RGBColor.from_string(GREEN)
p=c.add_paragraph('If the website is taken together with the School Management System, the website price will be ')
r=p.add_run('P5,500'); r.bold=True; r.font.size=Pt(13); r.font.color.rgb=RGBColor.from_string(NAVY); p.add_run('.')

heading('3. Delivery and payment',1)
heading('Delivery period',2)
p=doc.add_paragraph('Estimated delivery time is '); r=p.add_run('2 to 4 weeks'); r.bold=True; p.add_run(' from the date of approval and receipt of required content.')
heading('Payment terms',2)
bullet('50% deposit before commencement of work.')
bullet('50% upon completion and handover.')

heading('Notes',2)
for x in ['Domain registration and hosting fees are excluded unless otherwise agreed.','Any additional functionality requested outside the agreed scope may be quoted separately.','Basic training on how to update the website will be provided.']:
    bullet(x)

heading('Acceptance',2)
sig=doc.add_table(rows=4,cols=2); sig.autofit=False; sig.alignment=WD_TABLE_ALIGNMENT.LEFT; borders(sig)
sig.columns[0].width=Inches(3.2); sig.columns[1].width=Inches(3.2)
for i,h in enumerate(['For Swaneng English Medium Primary School','For DigitalCore Business Systems']):
    shade(sig.cell(0,i),PALE); margins(sig.cell(0,i),110,120,110,120)
    r=sig.cell(0,i).paragraphs[0].add_run(h); r.bold=True; r.font.size=Pt(9); r.font.color.rgb=RGBColor.from_string(NAVY)
for ri,label in enumerate(['Name:','Signature:','Date:'],1):
    for c in sig.rows[ri].cells:
        margins(c,60,120,60,120); p=c.paragraphs[0]; p.paragraph_format.space_after=Pt(0); p.add_run(label)

normal=doc.styles['Normal']; normal.font.name='Calibri'; normal.font.size=Pt(9.5); normal.font.color.rgb=TEXT
normal.paragraph_format.space_after=Pt(3); normal.paragraph_format.line_spacing=1.0
for s in ('Heading 1','Heading 2'):
    st=doc.styles[s]; st.paragraph_format.space_before=Pt(6); st.paragraph_format.space_after=Pt(2)
doc.core_properties.title='Website Development Quotation - Swaneng English Medium Primary School'
doc.core_properties.author='DigitalCore Business Systems'
doc.save(OUT)
print(OUT)
