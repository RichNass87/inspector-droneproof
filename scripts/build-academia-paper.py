#!/usr/bin/env python3
from __future__ import annotations

import html
import re
import sys
from pathlib import Path

from reportlab.graphics.shapes import Drawing, Line, Rect, String
from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_LEFT
from reportlab.lib.pagesizes import LETTER
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import inch
from reportlab.platypus import (
    Flowable,
    KeepTogether,
    ListFlowable,
    ListItem,
    PageBreak,
    Paragraph,
    SimpleDocTemplate,
    Spacer,
)


ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "papers" / "inspector-droneproof-evidence-architecture-v0.6.0.md"
OUTPUT = Path(sys.argv[1]).resolve() if len(sys.argv) > 1 else ROOT / "papers" / "inspector-droneproof-evidence-architecture-v0.6.0.pdf"

NAVY = colors.HexColor("#0B2742")
TEAL = colors.HexColor("#0D6B5D")
GOLD = colors.HexColor("#D5A940")
INK = colors.HexColor("#17211D")
MUTED = colors.HexColor("#50616B")
PAPER = colors.HexColor("#F4F1E8")
LINE = colors.HexColor("#C9D2D6")


class Rule(Flowable):
    def __init__(self, width: float, color=GOLD, thickness: float = 2):
        super().__init__()
        self.width = width
        self.height = thickness + 2
        self.color = color
        self.thickness = thickness

    def draw(self):
        self.canv.setStrokeColor(self.color)
        self.canv.setLineWidth(self.thickness)
        self.canv.line(0, self.height / 2, self.width, self.height / 2)


def inline_markup(text: str) -> str:
    escaped = html.escape(text, quote=False)
    escaped = re.sub(r"`([^`]+)`", r'<font name="Courier">\1</font>', escaped)
    escaped = re.sub(r"\[([^\]]+)\]\((https?://[^)]+)\)", r'<link href="\2" color="#0D6B5D">\1</link>', escaped)
    escaped = re.sub(r"(?<![\"=])(https?://[^\s<]+)", r'<link href="\1" color="#0D6B5D">\1</link>', escaped)
    return escaped


def system_flow_figure() -> Drawing:
    width, height = 468, 175
    drawing = Drawing(width, height)
    boxes = [
        (8, 102, 96, 48, "Authorized job", "WordPress intake"),
        (126, 102, 96, 48, "Pilot review", "Plan and checklist"),
        (244, 102, 96, 48, "Field capture", "Android photos"),
        (362, 102, 96, 48, "Evidence packet", "Labels and export"),
    ]
    for x, y, w, h, title, subtitle in boxes:
        drawing.add(Rect(x, y, w, h, rx=7, ry=7, fillColor=PAPER, strokeColor=TEAL, strokeWidth=1.2))
        drawing.add(String(x + w / 2, y + 29, title, textAnchor="middle", fontName="Helvetica-Bold", fontSize=9, fillColor=NAVY))
        drawing.add(String(x + w / 2, y + 14, subtitle, textAnchor="middle", fontName="Helvetica", fontSize=7.2, fillColor=MUTED))
    for x1, x2 in [(104, 126), (222, 244), (340, 362)]:
        drawing.add(Line(x1, 126, x2 - 4, 126, strokeColor=GOLD, strokeWidth=2))
        drawing.add(Line(x2 - 9, 130, x2 - 4, 126, strokeColor=GOLD, strokeWidth=2))
        drawing.add(Line(x2 - 9, 122, x2 - 4, 126, strokeColor=GOLD, strokeWidth=2))
    drawing.add(Rect(84, 20, 300, 48, rx=7, ry=7, fillColor=colors.HexColor("#E9F2EF"), strokeColor=LINE, strokeWidth=1))
    drawing.add(String(234, 48, "Human review and safety boundary", textAnchor="middle", fontName="Helvetica-Bold", fontSize=10, fillColor=NAVY))
    drawing.add(String(234, 32, "No DJI SDK binaries, aircraft command, coverage decision, or guaranteed outcome", textAnchor="middle", fontName="Helvetica", fontSize=7.4, fillColor=MUTED))
    drawing.add(Line(234, 102, 234, 68, strokeColor=LINE, strokeWidth=1.2))
    return drawing


def page_decor(canvas, doc):
    canvas.saveState()
    width, height = LETTER
    canvas.setFillColor(NAVY)
    canvas.rect(0, height - 20, width, 20, fill=1, stroke=0)
    canvas.setStrokeColor(GOLD)
    canvas.setLineWidth(1)
    canvas.line(0.65 * inch, 0.55 * inch, width - 0.65 * inch, 0.55 * inch)
    canvas.setFillColor(MUTED)
    canvas.setFont("Helvetica", 7.5)
    canvas.drawString(0.65 * inch, 0.36 * inch, "Inspector DroneProof v0.6.0 - first-party technical paper")
    canvas.drawRightString(width - 0.65 * inch, 0.36 * inch, f"Page {doc.page}")
    canvas.restoreState()


def build_story(markdown_text: str):
    styles = getSampleStyleSheet()
    title = ParagraphStyle(
        "PaperTitle",
        parent=styles["Title"],
        fontName="Helvetica-Bold",
        fontSize=24,
        leading=28,
        textColor=NAVY,
        alignment=TA_LEFT,
        spaceAfter=16,
    )
    metadata = ParagraphStyle(
        "Metadata",
        parent=styles["Normal"],
        fontName="Helvetica",
        fontSize=9.2,
        leading=13,
        textColor=MUTED,
        spaceAfter=3,
    )
    notice = ParagraphStyle(
        "Notice",
        parent=styles["Normal"],
        fontName="Helvetica-Bold",
        fontSize=8.6,
        leading=12,
        textColor=NAVY,
        backColor=colors.HexColor("#E9F2EF"),
        borderColor=TEAL,
        borderWidth=0.8,
        borderPadding=9,
        spaceBefore=10,
        spaceAfter=16,
    )
    heading = ParagraphStyle(
        "SectionHeading",
        parent=styles["Heading2"],
        fontName="Helvetica-Bold",
        fontSize=14,
        leading=17,
        textColor=NAVY,
        spaceBefore=15,
        spaceAfter=7,
        keepWithNext=True,
    )
    body = ParagraphStyle(
        "Body",
        parent=styles["BodyText"],
        fontName="Times-Roman",
        fontSize=10.2,
        leading=14.2,
        textColor=INK,
        alignment=TA_LEFT,
        spaceAfter=8,
    )
    reference = ParagraphStyle(
        "Reference",
        parent=body,
        fontSize=9.5,
        leading=13,
        leftIndent=10,
        firstLineIndent=-10,
        spaceAfter=6,
    )
    bullet = ParagraphStyle(
        "Bullet",
        parent=body,
        leftIndent=0,
        firstLineIndent=0,
        spaceAfter=3,
    )
    caption = ParagraphStyle(
        "Caption",
        parent=styles["Normal"],
        fontName="Helvetica-Oblique",
        fontSize=8,
        leading=10,
        textColor=MUTED,
        alignment=TA_CENTER,
        spaceAfter=10,
    )

    lines = markdown_text.splitlines()
    story = []
    paragraph_buffer = []
    bullets = []
    title_seen = False
    metadata_count = 0

    def flush_paragraph():
        nonlocal paragraph_buffer
        if paragraph_buffer:
            text = " ".join(part.strip() for part in paragraph_buffer)
            if re.match(r"^\d+\.\s", text):
                story.append(KeepTogether([Paragraph(inline_markup(text), reference)]))
            else:
                use_style = notice if text.startswith("First-party technical paper.") else body
                story.append(Paragraph(inline_markup(text), use_style))
            paragraph_buffer = []

    def flush_bullets():
        nonlocal bullets
        if bullets:
            items = [ListItem(Paragraph(inline_markup(item), bullet), leftIndent=13) for item in bullets]
            story.append(ListFlowable(items, bulletType="bullet", start="circle", leftIndent=18, bulletFontName="Helvetica", bulletFontSize=6, spaceAfter=8))
            bullets = []

    for line in lines:
        stripped = line.strip()
        if stripped.startswith("# "):
            flush_paragraph()
            flush_bullets()
            story.append(Spacer(1, 0.35 * inch))
            story.append(Paragraph(inline_markup(stripped[2:]), title))
            story.append(Rule(5.2 * inch))
            story.append(Spacer(1, 10))
            title_seen = True
            continue
        if stripped.startswith("## "):
            flush_paragraph()
            flush_bullets()
            story.append(Paragraph(inline_markup(stripped[3:]), heading))
            continue
        if stripped == "[[SYSTEM_FLOW_FIGURE]]":
            flush_paragraph()
            flush_bullets()
            story.append(KeepTogether([system_flow_figure(), Paragraph("Figure 1. DroneProof documentation flow and the human-review boundary.", caption)]))
            continue
        if stripped.startswith("- "):
            flush_paragraph()
            bullets.append(stripped[2:])
            continue
        if not stripped:
            flush_paragraph()
            flush_bullets()
            continue
        if title_seen and metadata_count < 6 and (stripped.startswith("Richard ") or stripped.startswith("Inspector Roofing") or stripped.startswith("ORCID:") or stripped.startswith("Project DOI:") or stripped.startswith("Evidence/source release:") or stripped.startswith("Published:")):
            flush_paragraph()
            flush_bullets()
            story.append(Paragraph(inline_markup(stripped), metadata))
            metadata_count += 1
            continue
        paragraph_buffer.append(stripped)

    flush_paragraph()
    flush_bullets()
    return story


def main():
    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    text = SOURCE.read_text(encoding="utf-8")
    doc = SimpleDocTemplate(
        str(OUTPUT),
        pagesize=LETTER,
        rightMargin=0.72 * inch,
        leftMargin=0.72 * inch,
        topMargin=0.58 * inch,
        bottomMargin=0.72 * inch,
        title="Inspector DroneProof: A Human-Reviewed Architecture for Roof Documentation, Field-Photo Intake, and Reproducible Evidence Packets",
        author="Richard Amir Nasser",
        subject="First-party technical description of Inspector DroneProof v0.6.0",
        creator="ORLOJ Engineering / Inspector Roofing and Restoration",
    )
    doc.build(build_story(text), onFirstPage=page_decor, onLaterPages=page_decor)
    print(OUTPUT)


if __name__ == "__main__":
    main()
