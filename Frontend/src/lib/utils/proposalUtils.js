export const buildProposalPayload = (data, categoryId) => {
  const payload = {
    category_id: categoryId,
    name: data.name,
    description: data.description,
  };

  if (data.image_path && data.image_path.trim() !== '') {
    payload.images = [
      {
        path: data.image_path.trim(),
        disk: 'public',
      }
    ];
  }

  if (data.extra_fields && data.extra_fields.length > 0) {
    const extraDataParsed = {};
    data.extra_fields.forEach(field => {
      let val = field.value;
      if (!isNaN(val) && val.trim() !== '') {
        val = Number(val);
      } else if (val.toLowerCase() === 'true') {
        val = true;
      } else if (val.toLowerCase() === 'false') {
        val = false;
      }
      extraDataParsed[field.key] = val;
    });
    payload.extra_data = extraDataParsed;
  }

  return payload;
};

