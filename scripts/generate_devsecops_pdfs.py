from pathlib import Path
import re

from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import cm
from reportlab.platypus import PageBreak, Paragraph, SimpleDocTemplate, Spacer


ROOT = Path(__file__).resolve().parents[1]
OUTPUT = ROOT / "output" / "pdf"

DOCUMENTS = [
    ("documentation-technique", "Documentation technique - Pipeline DevSecOps"),
    ("rapport-tests-unitaires", "Rapport des tests unitaires et fonctionnels"),
    ("rapport-analyses-securite", "Rapport des analyses de securite"),
    ("rapport-final-vulnerabilites", "Rapport final - Vulnerabilites et recommandations"),
]


def escape(text: str) -> str:
    return text.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;")


def styles():
    sheet = getSampleStyleSheet()
    sheet.add(
        ParagraphStyle(
            name="CoverTitle",
            parent=sheet["Title"],
            fontSize=22,
            leading=28,
            alignment=1,
            spaceAfter=18,
        )
    )
    sheet.add(
        ParagraphStyle(
            name="SmallMono",
            parent=sheet["Code"],
            fontSize=7,
            leading=9,
        )
    )

    sheet["Heading1"].fontSize = 16
    sheet["Heading1"].spaceBefore = 14
    sheet["Heading1"].spaceAfter = 8
    sheet["Heading2"].fontSize = 13
    sheet["Heading2"].spaceBefore = 10
    sheet["Heading2"].spaceAfter = 6
    sheet["Normal"].fontSize = 10
    sheet["Normal"].leading = 13

    return sheet


def inline_markdown(text: str) -> str:
    text = escape(text)
    text = re.sub(r"`([^`]+)`", r'<font name="Courier">\1</font>', text)
    text = re.sub(r"\*\*([^*]+)\*\*", r"<b>\1</b>", text)

    return text


def add_markdown(story, markdown: str, sheet) -> None:
    in_code = False
    code_lines: list[str] = []

    for raw in markdown.splitlines():
        line = raw.rstrip()

        if line.startswith("```"):
            if not in_code:
                in_code = True
                code_lines = []
            else:
                story.append(
                    Paragraph("<br/>".join(escape(item) for item in code_lines), sheet["SmallMono"])
                )
                story.append(Spacer(1, 6))
                in_code = False

            continue

        if in_code:
            code_lines.append(line)
            continue

        if not line.strip():
            story.append(Spacer(1, 5))
            continue

        if line.startswith("# "):
            story.append(Paragraph(escape(line[2:]), sheet["Heading1"]))
            continue

        if line.startswith("## "):
            story.append(Paragraph(escape(line[3:]), sheet["Heading2"]))
            continue

        if line.startswith("### "):
            story.append(Paragraph(f"<b>{escape(line[4:])}</b>", sheet["Normal"]))
            continue

        if line.startswith("- "):
            story.append(Paragraph(f"• {inline_markdown(line[2:])}", sheet["Normal"]))
            continue

        if line.startswith("|"):
            story.append(Paragraph(escape(line), sheet["SmallMono"]))
            continue

        story.append(Paragraph(inline_markdown(line), sheet["Normal"]))


def footer(canvas, doc) -> None:
    canvas.saveState()
    canvas.setFont("Helvetica", 8)
    canvas.setFillColor(colors.grey)
    canvas.drawString(2 * cm, 1.2 * cm, "Mohamed Habib Baili - L2T - Pipeline DevSecOps")
    canvas.drawRightString(A4[0] - 2 * cm, 1.2 * cm, f"Page {doc.page}")
    canvas.restoreState()


def build_pdf(stem: str, title: str) -> Path:
    OUTPUT.mkdir(parents=True, exist_ok=True)
    sheet = styles()
    markdown = (ROOT / "docs" / "devsecops" / f"{stem}.md").read_text(encoding="utf-8")
    pdf_path = OUTPUT / f"{stem}.pdf"

    doc = SimpleDocTemplate(
        str(pdf_path),
        pagesize=A4,
        rightMargin=2 * cm,
        leftMargin=2 * cm,
        topMargin=2 * cm,
        bottomMargin=2 * cm,
    )

    story = [
        Paragraph(title, sheet["CoverTitle"]),
        Paragraph("Stagiaire: Mohamed Habib Baili", sheet["Normal"]),
        Paragraph("Encadrant: Baylassen", sheet["Normal"]),
        Paragraph("Entreprise: L2T", sheet["Normal"]),
        Paragraph("Projet: Krayin Laravel CRM", sheet["Normal"]),
        Paragraph("Date: 2026-06-28", sheet["Normal"]),
        PageBreak(),
    ]

    add_markdown(story, markdown, sheet)
    doc.build(story, onFirstPage=footer, onLaterPages=footer)

    return pdf_path


if __name__ == "__main__":
    for document in DOCUMENTS:
        print(build_pdf(*document))
