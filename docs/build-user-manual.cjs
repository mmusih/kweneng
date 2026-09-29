const fs = require("fs");
const path = require("path");
const {
  AlignmentType,
  BorderStyle,
  Document,
  Footer,
  Header,
  HeadingLevel,
  ImageRun,
  LevelFormat,
  Packer,
  PageBreak,
  PageNumber,
  Paragraph,
  ShadingType,
  Table,
  TableCell,
  TableOfContents,
  TableRow,
  TextRun,
  WidthType,
} = require("docx");

const docsDir = __dirname;
const sourcePath = path.join(docsDir, "USER_MANUAL.md");
const outputPath = path.join(docsDir, "Kweneng_User_Manual.docx");
const logoPath = path.join(docsDir, "..", "public", "images", "logo.png");
const markdown = fs.readFileSync(sourcePath, "utf8").replace(/\r\n/g, "\n");

const navy = "163A5F";
const blue = "0B72B9";
const paleBlue = "EAF4FB";
const grey = "5B6573";
const border = { style: BorderStyle.SINGLE, size: 4, color: "CBD5E1" };

function stripInline(value) {
  return value
    .replace(/\*\*(.*?)\*\*/g, "$1")
    .replace(/`(.*?)`/g, "$1")
    .replace(/\[(.*?)\]\((.*?)\)/g, "$1");
}

function inlineRuns(value, options = {}) {
  const parts = value.split(/(\*\*.*?\*\*|`.*?`)/g).filter(Boolean);
  return parts.map((part) => {
    const bold = part.startsWith("**") && part.endsWith("**");
    const code = part.startsWith("`") && part.endsWith("`");
    const text = bold ? part.slice(2, -2) : code ? part.slice(1, -1) : part;
    return new TextRun({
      text,
      bold: bold || options.bold,
      italics: options.italics,
      color: options.color,
      font: code ? "Consolas" : "Aptos",
      size: options.size || 21,
    });
  });
}

function tableFrom(lines) {
  const rows = lines
    .filter((_, index) => index !== 1)
    .map((line) => line.trim().replace(/^\||\|$/g, "").split("|").map((v) => v.trim()));
  const columnCount = Math.max(...rows.map((row) => row.length));
  return new Table({
    width: { size: 100, type: WidthType.PERCENTAGE },
    rows: rows.map((row, rowIndex) =>
      new TableRow({
        tableHeader: rowIndex === 0,
        cantSplit: true,
        children: Array.from({ length: columnCount }, (_, cellIndex) =>
          new TableCell({
            shading: rowIndex === 0 ? { fill: navy, type: ShadingType.CLEAR } : undefined,
            borders: { top: border, bottom: border, left: border, right: border },
            margins: { top: 90, bottom: 90, left: 100, right: 100 },
            children: [
              new Paragraph({
                children: inlineRuns(row[cellIndex] || "", {
                  bold: rowIndex === 0,
                  color: rowIndex === 0 ? "FFFFFF" : "1F2937",
                  size: 18,
                }),
                spacing: { after: 0 },
              }),
            ],
          })
        ),
      })
    ),
  });
}

function parseBody(text) {
  const lines = text.split("\n");
  const children = [];
  let i = 0;
  let orderedIndex = 0;

  while (i < lines.length) {
    const raw = lines[i];
    const line = raw.trim();

    if (!line || line === "---") {
      i += 1;
      continue;
    }

    if (line.startsWith("|") && i + 1 < lines.length && /^\|?[\s:|-]+\|/.test(lines[i + 1])) {
      const tableLines = [lines[i], lines[i + 1]];
      i += 2;
      while (i < lines.length && lines[i].trim().startsWith("|")) {
        tableLines.push(lines[i]);
        i += 1;
      }
      children.push(tableFrom(tableLines));
      children.push(new Paragraph({ spacing: { after: 100 } }));
      continue;
    }

    const heading = line.match(/^(#{1,4})\s+(.+)$/);
    if (heading) {
      const level = heading[1].length;
      const headingMap = {
        1: HeadingLevel.HEADING_1,
        2: HeadingLevel.HEADING_2,
        3: HeadingLevel.HEADING_3,
        4: HeadingLevel.HEADING_4,
      };
      children.push(
        new Paragraph({
          text: stripInline(heading[2]),
          heading: headingMap[level],
          pageBreakBefore: level === 2 && /^([5-9]|1[0-9])\./.test(heading[2]),
          keepNext: true,
          spacing: { before: level === 2 ? 220 : 140, after: 90 },
        })
      );
      orderedIndex = 0;
      i += 1;
      continue;
    }

    if (line.startsWith("> ")) {
      children.push(
        new Paragraph({
          children: inlineRuns(line.slice(2), { italics: true, color: navy }),
          shading: { fill: paleBlue, type: ShadingType.CLEAR },
          border: { left: { style: BorderStyle.SINGLE, size: 18, color: blue } },
          indent: { left: 240, right: 120 },
          spacing: { before: 80, after: 120 },
        })
      );
      i += 1;
      continue;
    }

    const ordered = line.match(/^(\d+)\.\s+(.+)$/);
    if (ordered) {
      orderedIndex += 1;
      children.push(
        new Paragraph({
          children: inlineRuns(ordered[2]),
          numbering: { reference: "ordered-list", level: 0 },
          spacing: { after: 50 },
        })
      );
      i += 1;
      continue;
    }

    const bullet = line.match(/^-\s+(.+)$/);
    if (bullet) {
      children.push(
        new Paragraph({
          children: inlineRuns(bullet[1]),
          bullet: { level: 0 },
          spacing: { after: 50 },
        })
      );
      i += 1;
      continue;
    }

    const paragraphLines = [line];
    i += 1;
    while (
      i < lines.length &&
      lines[i].trim() &&
      !/^(#{1,4})\s+/.test(lines[i].trim()) &&
      !/^(\d+)\.\s+/.test(lines[i].trim()) &&
      !/^-\s+/.test(lines[i].trim()) &&
      !lines[i].trim().startsWith("> ") &&
      !lines[i].trim().startsWith("|") &&
      lines[i].trim() !== "---"
    ) {
      paragraphLines.push(lines[i].trim());
      i += 1;
    }
    children.push(
      new Paragraph({
        children: inlineRuns(paragraphLines.join(" ")),
        alignment: AlignmentType.JUSTIFIED,
        spacing: { after: 100, line: 290 },
      })
    );
  }

  return children;
}

const firstSectionMarker = markdown.indexOf("## 1. About this manual");
const bodyMarkdown = markdown.slice(firstSectionMarker);
const logo = fs.existsSync(logoPath)
  ? new ImageRun({ data: fs.readFileSync(logoPath), transformation: { width: 145, height: 145 } })
  : null;

const cover = [
  new Paragraph({ spacing: { before: 700 } }),
  ...(logo
    ? [
        new Paragraph({
          children: [logo],
          alignment: AlignmentType.CENTER,
          spacing: { after: 280 },
        }),
      ]
    : []),
  new Paragraph({
    children: [new TextRun({ text: "KWENENG INTERNATIONAL", bold: true, color: navy, size: 42, font: "Aptos Display" })],
    alignment: AlignmentType.CENTER,
  }),
  new Paragraph({
    children: [new TextRun({ text: "SECONDARY SCHOOL", bold: true, color: blue, size: 32, font: "Aptos Display" })],
    alignment: AlignmentType.CENTER,
    spacing: { after: 420 },
  }),
  new Paragraph({
    children: [new TextRun({ text: "School Management System", bold: true, color: navy, size: 52, font: "Aptos Display" })],
    alignment: AlignmentType.CENTER,
  }),
  new Paragraph({
    children: [new TextRun({ text: "USER MANUAL", bold: true, color: blue, size: 44, font: "Aptos Display" })],
    alignment: AlignmentType.CENTER,
    spacing: { after: 520 },
  }),
  new Paragraph({
    children: [new TextRun({ text: "Web Portal • Parent App • Teacher App", color: grey, size: 24 })],
    alignment: AlignmentType.CENTER,
    spacing: { after: 600 },
  }),
  new Paragraph({
    children: [new TextRun({ text: "Version 1.0  |  30 July 2026", color: grey, size: 20 })],
    alignment: AlignmentType.CENTER,
  }),
  new Paragraph({ children: [new PageBreak()] }),
  new Paragraph({
    text: "Contents",
    heading: HeadingLevel.HEADING_1,
    spacing: { after: 180 },
  }),
  new TableOfContents("Table of Contents", {
    hyperlink: true,
    headingStyleRange: "1-3",
  }),
  new Paragraph({ children: [new PageBreak()] }),
];

const doc = new Document({
  creator: "Kweneng International Secondary School",
  title: "Kweneng School Management System User Manual",
  description: "Role-based user manual for the Kweneng web portal and mobile applications.",
  styles: {
    default: {
      document: { run: { font: "Aptos", size: 21, color: "1F2937" } },
      heading1: { run: { font: "Aptos Display", size: 36, bold: true, color: navy }, paragraph: { spacing: { before: 260, after: 120 } } },
      heading2: { run: { font: "Aptos Display", size: 30, bold: true, color: navy }, paragraph: { spacing: { before: 220, after: 100 } } },
      heading3: { run: { font: "Aptos Display", size: 25, bold: true, color: blue }, paragraph: { spacing: { before: 150, after: 80 } } },
      heading4: { run: { font: "Aptos", size: 22, bold: true, color: navy }, paragraph: { spacing: { before: 120, after: 60 } } },
    },
  },
  numbering: {
    config: [
      {
        reference: "ordered-list",
        levels: [
          {
            level: 0,
            format: LevelFormat.DECIMAL,
            text: "%1.",
            alignment: AlignmentType.START,
            style: { paragraph: { indent: { left: 420, hanging: 240 } } },
          },
        ],
      },
    ],
  },
  sections: [
    {
      properties: {
        page: {
          margin: { top: 850, right: 850, bottom: 800, left: 850 },
        },
      },
      headers: {
        default: new Header({
          children: [
            new Paragraph({
              children: [
                new TextRun({ text: "KISS MANAGEMENT SYSTEM  •  USER MANUAL", color: grey, size: 16, bold: true }),
              ],
              border: { bottom: { style: BorderStyle.SINGLE, size: 4, color: "D7DEE7" } },
              spacing: { after: 100 },
            }),
          ],
        }),
      },
      footers: {
        default: new Footer({
          children: [
            new Paragraph({
              children: [
                new TextRun({ text: "Kweneng International Secondary School   |   " }),
                new TextRun({ children: [PageNumber.CURRENT] }),
              ],
              alignment: AlignmentType.CENTER,
              color: grey,
            }),
          ],
        }),
      },
      children: [...cover, ...parseBody(bodyMarkdown)],
    },
  ],
});

Packer.toBuffer(doc).then((buffer) => {
  fs.writeFileSync(outputPath, buffer);
  process.stdout.write(`${outputPath}\n`);
});
