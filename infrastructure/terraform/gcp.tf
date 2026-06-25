resource "google_compute_network" "vpc" {
  name                    = "inventory-vpc-${var.environment}"
  auto_create_subnetworks = false
}

resource "google_compute_subnetwork" "subnet" {
  name          = "inventory-subnet-${var.environment}"
  ip_cidr_range = "10.0.0.0/20"
  region        = var.region
  network       = google_compute_network.vpc.id

  private_ip_google_access = true
}

resource "google_compute_global_address" "private_ip" {
  name          = "inventory-private-ip-${var.environment}"
  purpose       = "VPC_PEERING"
  address_type  = "INTERNAL"
  prefix_length = 16
  network       = google_compute_network.vpc.id
}

resource "google_service_networking_connection" "private_vpc" {
  network                 = google_compute_network.vpc.id
  service                 = "servicenetworking.googleapis.com"
  reserved_peering_ranges = [google_compute_global_address.private_ip.name]
}

resource "google_service_account" "inventory_api" {
  account_id   = "inventory-api"
  display_name = "Inventory API Service Account"
}

resource "google_service_account" "inventory_db" {
  account_id   = "inventory-db"
  display_name = "Inventory DB Service Account"
}

resource "google_service_account" "inventory_monitoring" {
  account_id   = "inventory-monitoring"
  display_name = "Inventory Monitoring Service Account"
}

resource "google_service_account" "inventory_backup" {
  account_id   = "inventory-backup"
  display_name = "Inventory Backup Service Account"
}

resource "google_sql_database_instance" "inventory" {
  name             = "inventory-db-${var.environment}"
  database_version = "POSTGRES_16"
  region           = var.region

  settings {
    tier              = var.db_tier
    availability_type = "REGIONAL"
    disk_autoresize   = true
    disk_size         = 100

    backup_configuration {
      enabled                        = true
      point_in_time_recovery_enabled = true
      start_time                     = "03:00"
      transaction_log_retention_days = 7
      backup_retention_settings {
        retained_backups = 30
      }
    }

    ip_configuration {
      ipv4_enabled    = false
      private_network = google_compute_network.vpc.id
    }

    database_flags {
      name  = "max_connections"
      value = "200"
    }
  }

  depends_on = [google_service_networking_connection.private_vpc]
}

resource "google_sql_database" "inventory" {
  name     = "inventory"
  instance = google_sql_database_instance.inventory.name
}

resource "google_sql_user" "inventory" {
  name     = "inventory"
  instance = google_sql_database_instance.inventory.name
  password = random_password.db_password.result
}

resource "random_password" "db_password" {
  length  = 32
  special = true
}

resource "google_secret_manager_secret" "db_password" {
  secret_id = "inventory-db-password"
  replication {
    auto {}
  }
}

resource "google_secret_manager_secret_version" "db_password" {
  secret      = google_secret_manager_secret.db_password.id
  secret_data = random_password.db_password.result
}

resource "google_redis_instance" "inventory" {
  name               = "inventory-redis-${var.environment}"
  tier               = "STANDARD_HA"
  memory_size_gb     = var.redis_memory_gb
  region             = var.region
  authorized_network = google_compute_network.vpc.id
  redis_version      = "REDIS_7_0"
}

resource "google_storage_bucket" "documents" {
  name                        = "${var.project_id}-inventory-documents"
  location                    = var.region
  uniform_bucket_level_access = true
  versioning {
    enabled = true
  }
  lifecycle_rule {
    action {
      type          = "SetStorageClass"
      storage_class = "NEARLINE"
    }
    condition {
      age = 30
    }
  }
  lifecycle_rule {
    action {
      type          = "SetStorageClass"
      storage_class = "COLDLINE"
    }
    condition {
      age = 90
    }
  }
  lifecycle_rule {
    action {
      type          = "SetStorageClass"
      storage_class = "ARCHIVE"
    }
    condition {
      age = 365
    }
  }
}

resource "google_storage_bucket" "backups" {
  name                        = "${var.project_id}-inventory-backups"
  location                    = var.region
  uniform_bucket_level_access = true
  versioning {
    enabled = true
  }
}

resource "google_storage_bucket" "images" {
  name                        = "${var.project_id}-inventory-images"
  location                    = var.region
  uniform_bucket_level_access = true
  versioning {
    enabled = true
  }
}

resource "google_artifact_registry_repository" "inventory" {
  location      = var.region
  repository_id = "inventory-api"
  format        = "DOCKER"
}

resource "google_container_cluster" "inventory" {
  name     = "inventory-cluster-${var.environment}"
  location = var.region

  enable_autopilot = true
  network          = google_compute_network.vpc.name
  subnetwork       = google_compute_subnetwork.subnet.name

  private_cluster_config {
    enable_private_nodes    = true
    enable_private_endpoint = false
    master_global_access_config {
      enabled = true
    }
  }

  workload_identity_config {
    workload_pool = "${var.project_id}.svc.id.goog"
  }

  release_channel {
    channel = "REGULAR"
  }
}

resource "google_pubsub_topic" "inventory_events" {
  name = "inventory-events"
}

resource "google_monitoring_notification_channel" "email" {
  display_name = "Inventory Alerts Email"
  type         = "email"
  labels = {
    email_address = "alerts@inventory.local"
  }
}

resource "google_monitoring_alert_policy" "high_cpu" {
  display_name = "Inventory API High CPU"
  combiner     = "OR"
  conditions {
    display_name = "CPU > 80%"
    condition_threshold {
      filter          = "resource.type=\"k8s_container\" AND metric.type=\"kubernetes.io/container/cpu/core_usage_time\""
      duration        = "300s"
      comparison      = "COMPARISON_GT"
      threshold_value = 0.8
      aggregations {
        alignment_period   = "60s"
        per_series_aligner = "ALIGN_RATE"
      }
    }
  }
  notification_channels = [google_monitoring_notification_channel.email.id]
  alert_strategy {
    auto_close = "604800s"
  }
}
