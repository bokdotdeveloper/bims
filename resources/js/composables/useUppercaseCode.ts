/**
 * Normalize identifiers typed into Ant Design `a-input` / selects (`@update:value`).
 * Use for columns named like *_code (e.g. project_code).
 */
export function uppercaseCode(value: unknown): string {
    if (value === null || value === undefined) {
        return '';
    }

    return String(value).toUpperCase();
}
