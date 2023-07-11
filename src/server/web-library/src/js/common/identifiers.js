const cleanDOI = text => {
	const doi = text.match(/10(?:\.[0-9]{4,})?\/[^\s]*[^\s\.,]/);
	return doi ? doi[0] : null;
}

const cleanISBN = (isbnStr, dontValidate) => {
	isbnStr = isbnStr.toUpperCase()
		.replace(/[\x2D\xAD\u2010-\u2015\u2043\u2212]+/g, ''); // Ignore dashes
	var isbnRE = /\b(?:97[89]\s*(?:\d\s*){9}\d|(?:\d\s*){9}[\dX])\b/g,
		isbnMatch;

	// eslint-disable-next-line no-cond-assign
	while(isbnMatch = isbnRE.exec(isbnStr)) {
		var isbn = isbnMatch[0].replace(/\s+/g, '');

		if (dontValidate) {
			return isbn;
		}

		if(isbn.length == 10) {
			// Verify ISBN-10 checksum
			let sum = 0;
			for (let i = 0; i < 9; i++) {
				sum += isbn[i] * (10-i);
			}
			//check digit might be 'X'
			sum += (isbn[9] == 'X')? 10 : isbn[9]*1;

			if (sum % 11 == 0) return isbn;
		} else {
			// Verify ISBN 13 checksum
			let sum = 0;
			for (let i = 0; i < 12; i+=2) sum += isbn[i]*1;	//to make sure it's int
			for (let i = 1; i < 12; i+=2) sum += isbn[i]*3;
			sum += isbn[12]*1; //add the check digit

			if (sum % 10 == 0 ) return isbn;
		}

		isbnRE.lastIndex = isbnMatch.index + 1; // Retry the same spot + 1
	}

	return false;
}

// https://github.com/zotero/zotero/blob/57989260935703f0c7d570a39bcf6516b8c61df6/chrome/content/zotero/xpcom/utilities_internal.js#L1409
const extractIdentifiers = text => {
	const identifiers = [];
	const foundIDs = new Set(); // keep track of identifiers to avoid duplicates

	// First look for DOIs
	var ids = text.split(/[\s\u00A0]+/); // whitespace + non-breaking space
	var doi;
	for (let id of ids) {
		if ((doi = cleanDOI(id)) && !foundIDs.has(doi)) {
			identifiers.push({
				DOI: doi
			});
			foundIDs.add(doi);
		}
	}

	// Then try ISBNs
	if (!identifiers.length) {
		// First try replacing dashes
		let ids = text.replace(/[\u002D\u00AD\u2010-\u2015\u2212]+/g, "") // hyphens and dashes
			.toUpperCase();
		let ISBN_RE = /(?:\D|^)(97[89]\d{10}|\d{9}[\dX])(?!\d)/g;
		let isbn;

		// eslint-disable-next-line no-cond-assign
		while (isbn = ISBN_RE.exec(ids)) {
			isbn = cleanISBN(isbn[1]);
			if (isbn && !foundIDs.has(isbn)) {
				identifiers.push({
					ISBN: isbn
				});
				foundIDs.add(isbn);
			}
		}

		// Next try spaces
		if (!identifiers.length) {
			ids = ids.replace(/[ \u00A0]+/g, ""); // space + non-breaking space

			// eslint-disable-next-line no-cond-assign
			while (isbn = ISBN_RE.exec(ids)) {
				isbn = cleanISBN(isbn[1]);
				if(isbn && !foundIDs.has(isbn)) {
					identifiers.push({
						ISBN: isbn
					});
					foundIDs.add(isbn);
				}
			}
		}
	}

	// Next try arXiv
	if (!identifiers.length) {
		// arXiv identifiers are extracted without version number
		// i.e. 0706.0044v1 is extracted as 0706.0044,
		// because arXiv OAI API doesn't allow to access individual versions
		let arXiv_RE = /((?:[^A-Za-z]|^)([\-A-Za-z\.]+\/\d{7})(?:(v[0-9]+)|)(?!\d))|((?:\D|^)(\d{4}\.\d{4,5})(?:(v[0-9]+)|)(?!\d))/g;
		let m;
		while ((m = arXiv_RE.exec(text))) {
			let arXiv = m[2] || m[5];
			if (arXiv && !foundIDs.has(arXiv)) {
				identifiers.push({arXiv: arXiv});
				foundIDs.add(arXiv);
			}
		}
	}

	// Finally try for PMID
	if (!identifiers.length) {
		// PMID; right now, the longest PMIDs are 8 digits, so it doesn't seem like we'll
		// need to discriminate for a fairly long time
		let PMID_RE = /(^|\s|,|:)(\d{1,9})(?=\s|,|$)/g;
		let pmid;
		while ((pmid = PMID_RE.exec(text)) && !foundIDs.has(pmid)) {
			identifiers.push({
				PMID: pmid[2]
			});
			foundIDs.add(pmid);
		}
	}

	return identifiers;
}

export { cleanISBN, cleanDOI, extractIdentifiers };
