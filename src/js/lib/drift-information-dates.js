/**
 * Driftinformation cards get their date as plain text ("Publicerad X, uppdaterad Y")
 * because the date component escapes HTML. Split it into two lines with bold labels.
 */
const PATTERN = /^\s*(Publicerad)\s+(.+?)(?:,\s*(uppdaterad)\s+(.+?))?\s*$/i;

const capitalize = (text) => text.charAt(0).toUpperCase() + text.slice(1);

const addLine = (parent, label, date) => {
    const line = document.createElement('span');
    line.className = 'drift-date__line';

    const strong = document.createElement('strong');
    strong.textContent = `${capitalize(label)}:`;

    line.append(strong, ` ${date}`);
    parent.append(line);
};

export default function driftInformationDates() {
    document.querySelectorAll('.c-card__date time.c-date').forEach((time) => {
        const match = PATTERN.exec(time.textContent);

        if (!match) {
            return;
        }

        const [, publishedLabel, published, updatedLabel, updated] = match;

        time.textContent = '';
        time.classList.add('drift-date');
        addLine(time, publishedLabel, published);

        if (updated) {
            addLine(time, updatedLabel, updated);
        }
    });
}
