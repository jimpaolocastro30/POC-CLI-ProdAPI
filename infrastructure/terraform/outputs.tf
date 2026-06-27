output "environment" {
  value = var.environment
}

output "gke_cluster_name" {
  value = google_container_cluster.inventory.name
}

output "cloud_sql_connection" {
  value = google_sql_database_instance.inventory.connection_name
}

output "cloud_sql_private_ip" {
  value = google_sql_database_instance.inventory.private_ip_address
}

output "redis_host" {
  value = google_redis_instance.inventory.host
}

output "db_password_secret" {
  value = google_secret_manager_secret.db_password.secret_id
}

output "storage_buckets" {
  value = [
    google_storage_bucket.documents.name,
    google_storage_bucket.backups.name,
    google_storage_bucket.images.name,
  ]
}

output "artifact_registry" {
  value = var.create_artifact_registry ? google_artifact_registry_repository.inventory[0].id : "${var.region}-docker.pkg.dev/${var.project_id}/inventory-api"
}

output "inventory_api_service_account" {
  value = google_service_account.inventory_api.email
}

output "k8s_namespace" {
  value = "inventory-${var.environment}"
}
