variable "project_id" {
  description = "GCP Project ID"
  type        = string
}

variable "region" {
  description = "GCP region"
  type        = string
  default     = "us-central1"
}

variable "environment" {
  description = "Environment name (dev, staging, production)"
  type        = string
  default     = "production"

  validation {
    condition     = contains(["dev", "staging", "production"], var.environment)
    error_message = "environment must be dev, staging, or production."
  }
}

variable "db_tier" {
  description = "Cloud SQL instance tier"
  type        = string
  default     = "db-custom-4-16384"
}

variable "db_availability_type" {
  description = "ZONAL (cheaper) or REGIONAL (HA)"
  type        = string
  default     = "REGIONAL"
}

variable "db_disk_size_gb" {
  description = "Initial Cloud SQL disk size in GB"
  type        = number
  default     = 100
}

variable "db_max_connections" {
  description = "PostgreSQL max_connections flag"
  type        = number
  default     = 200
}

variable "redis_memory_gb" {
  description = "Memorystore Redis memory in GB"
  type        = number
  default     = 5
}

variable "redis_tier" {
  description = "BASIC (single zone, cheaper) or STANDARD_HA"
  type        = string
  default     = "STANDARD_HA"
}

variable "enable_pitr" {
  description = "Enable Cloud SQL point-in-time recovery"
  type        = bool
  default     = true
}

variable "backup_retained_count" {
  description = "Number of automated backups to retain"
  type        = number
  default     = 30
}

variable "enable_monitoring_alerts" {
  description = "Create Cloud Monitoring alert policies"
  type        = bool
  default     = true
}

variable "enable_storage_lifecycle" {
  description = "Apply GCS lifecycle rules (documents bucket)"
  type        = bool
  default     = true
}

variable "create_artifact_registry" {
  description = "Create Artifact Registry repo (set false if already exists in project)"
  type        = bool
  default     = true
}
