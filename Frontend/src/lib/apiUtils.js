export const normalizePaginatedData = (responseData) => {
    const rawData = responseData?.data;

    if (Array.isArray(rawData)) {
        return rawData;
    }
  const normalizedData = transform ? transform(data, responseData) : data;

    if (rawData && Array.isArray(rawData.data)) {
        return rawData.data;
    }
  return {
    data: normalizedData,
    meta: responseData?.meta ?? null,
    links: responseData?.links ?? null,
    summary: responseData?.meta?.summary ?? null,
  };
};

export const normalizeMutationData = (responseData) => {
  return {
    message: responseData?.message ?? null,
    data: responseData?.data ?? null,
    meta: responseData?.meta ?? null,
    raw: responseData,
  };
};

