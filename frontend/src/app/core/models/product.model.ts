export interface Product {
  '@id'?: string;
  id: number;
  name: string;
  sku: string | null;
  unitPrice: string;
  stockQuantity: number;
  createdAt: string;
}

export interface CreateProductPayload {
  name: string;
  sku?: string | null;
  unitPrice: string;
  stockQuantity: number;
}
